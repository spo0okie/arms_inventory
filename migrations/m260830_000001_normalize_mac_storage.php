<?php

namespace app\migrations;

use app\helpers\MacsHelper;
use app\migrations\arms\ArmsMigration;

/**
 * Приведение хранимых MAC-адресов к стандарту проекта.
 *
 * Стандарт один: поле mac — строки голого hex в нижнем регистре (диапазон —
 * компактная пара start-end), его определяет MacsHelper::fixList(). На него
 * рассчитан поиск (LIKE по подстроке hex): запись с разделителями поиск
 * молча промахивает, и адрес, который инвентаризация знает, выглядит
 * неопознанным.
 *
 * Нормализацию держит beforeSave обеих моделей (Techs — давно, Comps — с
 * этой же правки: фильтр валидации save(false) обходит, а так пишут агенты).
 * Миграция подчищает легаси, записанное до сторожей; на чистых данных не
 * меняет ничего и безопасна к повторному прогону.
 */
class m260830_000001_normalize_mac_storage extends ArmsMigration
{
	/** таблицы с полем mac */
	protected $tables = ['techs', 'comps'];

	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		foreach ($this->tables as $table) {
			$rows = (new \yii\db\Query())
				->select(['id', 'mac'])
				->from($table)
				->where(['not', ['mac' => null]])
				->andWhere(['<>', 'mac', ''])
				->all($this->db);

			$fixed = 0;
			foreach ($rows as $row) {
				$normalized = MacsHelper::fixList($row['mac']);
				if ($normalized === $row['mac']) continue;
				$this->update($table, ['mac' => $normalized], ['id' => $row['id']]);
				$fixed++;
			}
			echo "    > $table: нормализовано записей mac: $fixed\n";
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * Исходные написания не хранились — откатывать не к чему, да и незачем:
	 * нормализованная запись остаётся тем же адресом.
	 */
	public function down()
	{
		return true;
	}
}
