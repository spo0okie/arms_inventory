<?php

namespace tests\unit\components\integrations;

use app\components\integrations\CellsBatch;
use app\components\integrations\IntegrationProvider;
use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\PanelsCache;
use app\models\base\ArmsModel;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;

/**
 * Тесты серверной половины списочного режима интеграций
 * (docs/dev/integrations.md §5 «Колонки в списках»): CellsBatch — построчный кэш +
 * ОДИН батч-вызов renderCells() провайдера на все протухшие строки.
 * Внешних ИС нет — провайдер фейковый, считает вызовы.
 */
class CellsBatchTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Фейковый провайдер: применим к Users, привязка — Mobile, ячейки
	 * строит из привязки и считает батч-вызовы. Анонимный класс, а не
	 * top-level (extends app-класса на уровне файла требует автозагрузку
	 * до бутстрапа — фатал при загрузке теста).
	 * @return IntegrationProvider с публичными $calls/$lastBatch/$cellsResult
	 */
	private function makeProvider(array $config = []): IntegrationProvider
	{
		$provider = new class extends IntegrationProvider {
			/** @var int сколько раз позвали renderCells() */
			public int $calls = 0;

			/** @var ArmsModel[] модели последнего батча */
			public array $lastBatch = [];

			/** @var array|\Throwable|null null = построить из binding; исключение = бросить */
			public $cellsResult = null;

			public function getTitle(): string
			{
				return 'Fake';
			}

			public function isConfigured(): bool
			{
				return true;
			}

			public function appliesTo(ArmsModel $model): bool
			{
				return $model instanceof Users;
			}

			public function binding(ArmsModel $model): ?string
			{
				/** @var Users $model */
				return $model->Mobile ?: null;
			}

			public function gridColumns(string $modelClass): array
			{
				if (!is_a($modelClass, Users::class, true)) return [];
				$column = ['title' => 'Fake'];
				if (isset($this->config['columnTtl'])) $column['ttl'] = $this->config['columnTtl'];
				return ['status' => $column];
			}

			public function renderCells(string $columnId, array $models): array
			{
				$this->calls++;
				$this->lastBatch = $models;
				if ($this->cellsResult instanceof \Throwable) throw $this->cellsResult;
				if (is_array($this->cellsResult)) return $this->cellsResult;

				$cells = [];
				foreach ($models as $model) $cells[$model->id] = '<b>cell-'.$model->Mobile.'</b>';
				return $cells;
			}
		};
		$provider->id = 'cells-fake';
		$provider->config = $config;
		return $provider;
	}

	/** Users с id и уникальной привязкой (кэш не пересекается между прогонами) */
	private function makeUser(int $id, ?string $binding = 'auto'): Users
	{
		$user = new Users();
		$user->id = $id;
		$user->Mobile = $binding === 'auto' ? uniqid("bind$id-") : $binding;
		return $user;
	}

	/**
	 * Основной поток: неприменимые строки — пусто, применимые без
	 * привязки — заглушка «не привязан», привязанные — ОДИН батч-вызов
	 */
	public function testFlow()
	{
		$provider = $this->makeProvider();

		$bound1 = $this->makeUser(1);
		$bound2 = $this->makeUser(2);
		$unbound = $this->makeUser(3, null);
		$alien = new Techs();
		$alien->id = 4;

		$cells = CellsBatch::render($provider, 'status', Users::class,
			[$bound1, $bound2, $unbound, $alien]);

		$this->assertSame(1, $provider->calls, 'все привязанные строки — одним батчем');
		$this->assertCount(2, $provider->lastBatch);
		$this->assertSame('<b>cell-'.$bound1->Mobile.'</b>', $cells[1]);
		$this->assertSame('<b>cell-'.$bound2->Mobile.'</b>', $cells[2]);
		$this->assertStringContainsString('нет привязки', $cells[3]);
		$this->assertSame('', $cells[4], 'неприменимая строка — пустая ячейка');
	}

	/** Свежий построчный кэш: повторный рендер не зовёт провайдера */
	public function testCacheSkipsProvider()
	{
		$provider = $this->makeProvider();
		$user = $this->makeUser(5);

		$first = CellsBatch::render($provider, 'status', Users::class, [$user]);
		$second = CellsBatch::render($provider, 'status', Users::class, [$user]);

		$this->assertSame(1, $provider->calls, 'второй проход — целиком из кэша');
		$this->assertSame($first[5], $second[5]);
	}

	/**
	 * Ошибка внешней ИС — штатный исход: заглушка в ячейках,
	 * старый кэш не перетирается (контракт §3.1)
	 */
	public function testErrorKeepsCache()
	{
		$provider = $this->makeProvider();
		$user = $this->makeUser(6);
		$binding = $user->Mobile;

		//прогреваем кэш успешным проходом
		CellsBatch::render($provider, 'status', Users::class, [$user]);
		$cached = PanelsCache::fetch($provider->id, CellsBatch::cacheSlot('status'), $binding);
		$this->assertNotNull($cached);

		//старим кэш и ломаем провайдера
		touch(PanelsCache::path($provider->id, CellsBatch::cacheSlot('status'), $binding),
			time() - 3600);
		clearstatcache(); //filemtime иначе отдаст закэшированное «свежее» время
		$provider->cellsResult = new \RuntimeException('boom');

		$cells = CellsBatch::render($provider, 'status', Users::class, [$user]);
		$this->assertStringContainsString('integration-cell-error', $cells[6]);

		$after = PanelsCache::fetch($provider->id, CellsBatch::cacheSlot('status'), $binding);
		$this->assertSame($cached['html'], $after['html'], 'кэш ошибкой не перетёрт');
	}

	/** Строка, не возвращённая провайдером, — пустая ячейка без записи в кэш */
	public function testMissingIdNotCached()
	{
		$provider = $this->makeProvider();
		$provider->cellsResult = []; //провайдер «забыл» все строки
		$user = $this->makeUser(7);

		$cells = CellsBatch::render($provider, 'status', Users::class, [$user]);

		$this->assertSame('', $cells[7]);
		$this->assertNull(
			PanelsCache::fetch($provider->id, CellsBatch::cacheSlot('status'), $user->Mobile),
			'пустой результат не кэшируется'
		);
	}

	/**
	 * Разметка ячейки при рендере страницы (CellColumn): свежий кэш —
	 * как есть, протухший/пустой — контейнер с data-атрибутами для
	 * батч-скрипта, привязка/применимость — как в батче
	 */
	public function testRenderGridCell()
	{
		$provider = $this->makeProvider();

		//кэша нет — контейнер со спиннером и полным набором data-атрибутов
		$user = $this->makeUser(8);
		$html = CellsBatch::renderGridCell($provider, 'status', $user);
		$this->assertStringContainsString('integration-cell-stale', $html);
		$this->assertStringContainsString('spinner-border', $html);
		$this->assertStringContainsString('data-provider="cells-fake"', $html);
		$this->assertStringContainsString('data-column="status"', $html);
		$this->assertStringContainsString('data-class="users"', $html);
		$this->assertStringContainsString('data-id="8"', $html);
		$this->assertStringContainsString('data-url=', $html);

		//свежий кэш — HTML как есть, без контейнера и запроса
		CellsBatch::render($provider, 'status', Users::class, [$user]);
		$html = CellsBatch::renderGridCell($provider, 'status', $user);
		$this->assertSame('<b>cell-'.$user->Mobile.'</b>', $html);

		//протухший кэш — приглушённое содержимое внутри контейнера
		touch(PanelsCache::path($provider->id, CellsBatch::cacheSlot('status'), $user->Mobile),
			time() - 3600);
		clearstatcache();
		$html = CellsBatch::renderGridCell($provider, 'status', $user);
		$this->assertStringContainsString('integration-cell-stale', $html);
		$this->assertStringContainsString('cell-'.$user->Mobile, $html);
		$this->assertStringContainsString('opacity:.5', $html);

		//без привязки и для чужого класса — как в батче
		$this->assertStringContainsString('нет привязки',
			CellsBatch::renderGridCell($provider, 'status', $this->makeUser(9, null)));
		$alien = new Techs();
		$alien->id = 10;
		$this->assertSame('', CellsBatch::renderGridCell($provider, 'status', $alien));
	}

	/**
	 * Конфиги колонок для DynaGridWidget: ключ — стабильный attribute,
	 * класс CellColumn, скрыты по умолчанию (включаются персонализацией)
	 */
	public function testGridColumnConfigs()
	{
		$provider = $this->makeProvider();

		//реестр строится из params (нужен именованный класс) — для теста
		//кладём фейк в кэш реестра напрямую
		$registry = new \ReflectionProperty(IntegrationsRegistry::class, 'providers');
		$registry->setValue(null, ['cells-fake' => $provider]);
		try {
			$configs = IntegrationsRegistry::gridColumnConfigs(Users::class);
			$this->assertArrayHasKey('integration-cells-fake-status', $configs);
			$column = $configs['integration-cells-fake-status'];
			$this->assertSame(\app\components\integrations\CellColumn::class, $column['class']);
			$this->assertSame($provider, $column['provider']);
			$this->assertSame('status', $column['columnId']);
			$this->assertSame('Fake', $column['label']);
			$this->assertFalse($column['visible'], 'живые колонки скрыты по умолчанию');

			$this->assertSame([], IntegrationsRegistry::gridColumnConfigs(Techs::class),
				'класс без колонок — пусто');
		} finally {
			IntegrationsRegistry::reset();
		}
	}

	/** TTL ячейки: колонка > конфиг > дефолт, но не ниже MIN_CELL_TTL */
	public function testCellTtl()
	{
		$provider = $this->makeProvider(['columnTtl' => 120]);
		$this->assertSame(120, $provider->cellTtl('status', Users::class), 'ttl колонки');

		$provider = $this->makeProvider(['columnTtl' => null, 'cellTtl' => 45]);
		$this->assertSame(45, $provider->cellTtl('status', Users::class), 'ttl из конфига');

		$provider = $this->makeProvider();
		$this->assertSame(IntegrationProvider::DEFAULT_CELL_TTL,
			$provider->cellTtl('status', Users::class), 'дефолт');

		$provider = $this->makeProvider(['columnTtl' => 0]);
		$this->assertSame(IntegrationProvider::MIN_CELL_TTL,
			$provider->cellTtl('status', Users::class), '«обновлять всегда» для списков не предусмотрено');

		$this->assertSame([], $provider->gridColumns(Techs::class), 'чужой класс — без колонок');
	}
}
