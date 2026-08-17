<?php

namespace app\components;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;

/**
 * Журнал медленных web-запросов (docs/dev/perf-monitoring.md).
 *
 * По окончании обработки каждого запроса (EVENT_AFTER_REQUEST) сравнивает
 * длительность с порогом и, если превышен, пишет одну строку в лог категории
 * 'perf' (отдельный файл runtime/logs/perf.log, см. config/web.php).
 *
 * Идея: лог заранее отфильтрован — каждая строка в нём уже аномалия, поэтому
 * мониторинг тривиален ("файл растёт" = "системе плохо", Zabbix-триггер по
 * количеству строк за интервал, см. docs/help/admin/monitoring.md).
 * Тишина в файле = система не тормозит.
 *
 * Формат строки стабилен (на него завязан regexp Zabbix-триггера и возможный
 * последующий разбор):
 *   slow request: 12.345s status=200 route=services/view url="/services/view?id=5" user=ivanov mem=64.5M
 *
 * Регистрируется бутстрап-компонентом в web-конфиге; порог задаётся
 * params['perf.slow_request_seconds'] (0 = выключить). Консольные команды
 * компонент не затрагивает.
 *
 * Ограничение: запрос, убитый фатально (max_execution_time), до
 * EVENT_AFTER_REQUEST не доживает и сюда не попадает — такие случаи ловит
 * время ответа в access-логе Apache (%D).
 */
class SlowRequestLogger extends Component implements BootstrapInterface
{
	/** @var float порог в секундах; 0 или отрицательный = логирование выключено */
	public $threshold = 3;

	/** {@inheritdoc} */
	public function bootstrap($app)
	{
		if ($this->threshold <= 0 || !$app instanceof \yii\web\Application) return;
		$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'logIfSlow']);
	}

	/** обработчик EVENT_AFTER_REQUEST: пишет строку, если запрос дольше порога */
	public function logIfSlow()
	{
		$duration = microtime(true) - $this->requestStartTime();
		if ($duration < $this->threshold) return;
		Yii::warning($this->formatMessage($duration), 'perf');
	}

	/** старт обработки запроса: REQUEST_TIME_FLOAT ставится раньше автолоадера
	 * и потому честнее YII_BEGIN_TIME (который остаётся фолбэком) */
	protected function requestStartTime(): float
	{
		return $_SERVER['REQUEST_TIME_FLOAT'] ?? YII_BEGIN_TIME;
	}

	/** собирает строку лога; каждый источник данных обёрнут в защиту —
	 * диагностика не имеет права уронить сам запрос */
	protected function formatMessage(float $duration): string
	{
		$app = Yii::$app;
		$status = $route = $url = $user = '-';
		try {
			$status = (string)$app->response->statusCode;
			$route = $app->requestedRoute ?: '-';
			$url = $app->request->url;
		} catch (\Throwable $e) {
			//запрос мог развалиться до резолва маршрута
		}
		try {
			//identity может быть недоступна (гость, сломанная сессия, упавшая БД)
			$user = $app->user->identity->Login ?? 'guest';
		} catch (\Throwable $e) {
			//пользователь неизвестен - не страшно
		}
		return sprintf(
			'slow request: %.3fs status=%s route=%s url="%s" user=%s mem=%.1fM',
			$duration, $status, $route, $url, $user,
			memory_get_peak_usage(true) / 1024 / 1024
		);
	}
}
