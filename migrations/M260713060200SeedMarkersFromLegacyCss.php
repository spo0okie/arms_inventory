<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;
use yii\db\Query;

/**
 * Сид цветовых маркеров (issue #141) из легаси CSS-палитры
 * (web/css/state-colors.css и web/css/codes.private.css).
 *
 * Создает ВСЮ палитру маркеров (справочник — полная палитра для выбора,
 * независимо от того, какие коды сейчас используются в БД) и проставляет
 * marker_id в справочниках по существующим кодам (домены — по имени);
 * уже назначенные маркеры не перетираются.
 * Легаси CSS-классы остаются fallback'ом для записей без маркера.
 */
class M260713060200SeedMarkersFromLegacyCss extends ArmsMigration
{
	/**
	 * Пояснение сеяных маркеров: палитра переезжает из кода приложения
	 * (легаси CSS), а не из динамических данных предыдущей версии.
	 * По нему же down() отличает сеяные маркеры от созданных вручную.
	 */
	const SEED_COMMENT = 'Базовая палитра';

	/**
	 * @var array Палитра: ключ => [name, color, text_color, border_color, border_style]
	 */
	protected $palette = [
		'cyan' =>				['Голубой (cyan)', '#00FFFF'],
		'bright_blue' =>		['Ярко-синий', '#1DA7EE'],
		'gray' =>				['Серый', '#808080'],
		'green' =>				['Зелёный', '#5CB85C'],
		'orange' =>				['Оранжевый', '#FFA500'],
		'red' =>				['Красный', '#FF0000'],
		'gray_orange_text' =>	['Серый, оранжевый текст', '#808080', '#FFA500'],
		'magenta' =>			['Пурпурный', '#8B008B'],
		'yellow' =>				['Жёлтый', '#FFFF00'],
		'light_green' =>		['Светло-зелёный', '#90EE90'],
		'lime_green' =>			['Салатовый (limegreen)', '#32CD32'],
		'dark_green' =>			['Тёмно-зелёный', '#008000'],
		'pink' =>				['Розовый', '#FFC0CB'],
		'light_sky_blue' =>		['Светло-голубой', '#87CEFA'],
		'yellow_dmz' =>			['Жёлтый, красный пунктир', '#FFFF00', null, '#FF0000', 'dashed'],
		'lightgray_dmz' =>		['Светло-серый, оранжевый пунктир', '#D3D3D3', null, '#FFA500', 'dashed'],
		'lightgreen_dmz' =>		['Светло-зелёный, оранжевый пунктир', '#90EE90', null, '#FFA500', 'dashed'],
		'pink_dmz' =>			['Розовый, оранжевый пунктир', '#FFC0CB', null, '#FFA500', 'dashed'],
		'dark_guest' =>			['Тёмный, оранжевая рамка и текст', '#3C3C3C', '#FFA500', '#FFA500', 'solid'],
		'palegoldenrod' =>		['Палевый (palegoldenrod)', '#EEE8AA'],
		'light_yellow' =>		['Светло-жёлтый', '#FFFFE0'],
		'gw_open' =>			['Светло-зелёный, красная рамка', '#90EE90', null, '#FF0000', 'solid'],
		'gw_close' =>			['Розовый, зелёная рамка', '#FFC0CB', null, '#00FF00', 'solid'],
		'green_yellow' =>		['Жёлто-зелёный (greenyellow)', '#ADFF2F'],
		'burlywood' =>			['Бежевый (burlywood)', '#DEB887'],
		'light_olive' =>		['Светло-оливковый', '#EDEDCB'],
		'pastel_cyan' =>		['Пастельно-бирюзовый', '#96DCE1'],
		'gray_medium' =>		['Серый средний', '#949494'],
		'khaki_light' =>		['Хаки светлый', '#C9C9A3'],
		'pastel_yellow' =>		['Пастельно-жёлтый', '#EDEDB7'],
		'pastel_blue' =>		['Пастельно-синий', '#9DA1D7'],
		'pastel_lilac' =>		['Пастельно-лиловый', '#C080C0'],
	];

	/**
	 * @var array Привязка: таблица => [атрибут поиска, [значение => ключ палитры]]
	 */
	protected $map = [
		'tech_states' => ['code', [
			'state_required' =>				'cyan',
			'state_confirmed' =>			'bright_blue',
			'state_in_supply_service' =>	'cyan',
			'state_in_warehouse' =>			'gray',
			'state_operating' =>			'green',
			'state_malfunction' =>			'orange',
			'state_broken' =>				'red',
			'state_decommissioned' =>		'gray_orange_text',
			'state_issued' =>				'magenta',
		]],
		'contracts_states' => ['code', [
			'state_required' =>				'yellow',
			'state_paywait' =>				'yellow',
			'state_paywait_confirmed' =>	'light_green',
			'state_payed_partial' =>		'lime_green',
			'state_paywait_full' =>			'dark_green',
			'state_payed' =>				'bright_blue',
			'state_revoked' =>				'red',
			'state_fail' =>					'red',
		]],
		'segments' => ['code', [
			'segment_open' =>				'light_green',
			'segment_closed' =>				'pink',
			'segment_common' =>				'light_sky_blue',
			'segment_ext' =>				'yellow',
			'segment_ext_dmz' =>			'yellow_dmz',
			'segment_int_dmz' =>			'lightgray_dmz',
			'segment_open_dmz' =>			'lightgreen_dmz',
			'segment_closed_dmz' =>			'pink_dmz',
			'segment_guest_dmz' =>			'dark_guest',
			'segment_client_vpn' =>			'palegoldenrod',
			'segment_intersite_vpn' =>		'cyan',
			'segment_it_lan' =>				'orange',
			'segment_videoconf' =>			'light_yellow',
			'segment_gw_close2open' =>		'gw_open',
			'segment_gw_open2close' =>		'gw_close',
			'segment_voip' =>				'green_yellow',
			'segment_prn' =>				'gray',
			'segment_skud' =>				'burlywood',
			//segment_mgmt в CSS был штриховкой lightyellow/#e3e3e3 —
			//штриховку в маркеры не переносим, заменяем сплошным смешением
			'segment_mgmt' =>				'light_olive',
		]],
		'net_domains' => ['name', [
			'CHL_VLAN' =>	'pastel_cyan',
			'MSK_VLAN' =>	'gray_medium',
			'KLG_VLAN' =>	'khaki_light',
			'MHK_VLAN' =>	'pastel_yellow',
			'NN_VLAN' =>	'pastel_blue',
			'SPB_VLAN' =>	'pastel_lilac',
		]],
	];

	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		//вся палитра целиком: маркеры - готовые варианты для выбора,
		//даже если код пока не используется в этой инсталляции
		$markerIds = []; //ключ палитры => id созданного/существующего маркера
		foreach (array_keys($this->palette) as $paletteKey) {
			$markerIds[$paletteKey] = $this->fetchOrCreateMarker($paletteKey);
		}

		foreach ($this->map as $table => [$attr, $links]) {
			if (!$this->tableExists($table)) continue;
			foreach ($links as $value => $paletteKey) {
				//только записи, у которых маркер еще не назначен
				$rows = (new Query())->from($table)
					->where([$attr => $value, 'marker_id' => null])
					->select(['id'])->column($this->db);
				if (!count($rows)) continue;

				$this->update($table, ['marker_id' => $markerIds[$paletteKey]], ['id' => $rows]);
			}
		}
	}

	/**
	 * Находит маркер палитры по имени или создает его
	 * @param string $paletteKey
	 * @return int id маркера
	 */
	protected function fetchOrCreateMarker(string $paletteKey)
	{
		[$name, $color, $textColor, $borderColor, $borderStyle] =
			array_pad($this->palette[$paletteKey], 5, null);

		$existing = (new Query())->from('markers')
			->where(['name' => $name])
			->select(['id'])->scalar($this->db);
		if ($existing) return (int)$existing;

		$this->insert('markers', [
			'name' => $name,
			'color' => $color,
			'text_color' => $textColor,
			'border_color' => $borderColor,
			'border_style' => $borderStyle,
			'comment' => static::SEED_COMMENT,
			'updated_by' => 'migration',
		]);
		return (int)$this->db->getLastInsertID();
	}

	/**
	 * {@inheritdoc}
	 * Откат: отвязывает сеяные маркеры и удаляет их, если больше не используются.
	 */
	public function down()
	{
		$names = array_column($this->palette, 0);
		$seeded = (new Query())->from('markers')
			->where(['name' => $names, 'comment' => static::SEED_COMMENT])
			->select(['id'])->column($this->db);
		if (!count($seeded)) return;

		foreach (array_keys($this->map) as $table) {
			if (!$this->tableExists($table)) continue;
			$this->update($table, ['marker_id' => null], ['marker_id' => $seeded]);
		}
		$this->delete('markers', ['id' => $seeded]);
	}
}
