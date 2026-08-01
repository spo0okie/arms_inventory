<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Недостающие колонки created_at/created_by у справочников lic_types, lic_groups
 * и manufacturers.
 *
 * Модели этих таблиц с самого начала (легаси генерации моделей) объявляли
 * created_at в rules()/attributeData(), но колонки в БД не было: обращение к
 * $model->created_at падало с UnknownPropertyException (всплыло на механизме
 * копирования «по образцу», ArmsModel::copyPrefillAttributes()).
 *
 * Приводим схему к объявлению моделей: created_at заполняется на INSERT
 * (ArmsModel::beforeSave), created_by — учетная запись автора, по образцу
 * updated_by (varchar(32), логин, а не ID пользователя).
 *
 * Существующим записям время создания неизвестно, поэтому колонки nullable и
 * значения не досчитываются (updated_at — это время последнего изменения,
 * выдавать его за время создания было бы враньем).
 */
class M260801150000AddCreatedFieldsToDicts extends ArmsMigration
{
	/**
	 * Таблицы, которым не хватает служебных полей создания.
	 * lic_groups_history — зеркало lic_groups в журнале изменений: created_at
	 * там уже есть, добавляем парный created_by, чтобы зеркало было полным.
	 */
	public $tables = [
		'lic_types',
		'lic_groups',
		'manufacturers',
		'lic_groups_history',
	];

	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		foreach ($this->tables as $table) {
			$this->addColumnIfNotExists($table, 'created_at',
				$this->timestamp()->null()->comment('Время создания'));
			$this->addColumnIfNotExists($table, 'created_by',
				$this->string(32)->null()->comment('Автор создания'));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		foreach ($this->tables as $table) {
			$this->dropColumnIfExists($table, 'created_by');
			//created_at в lic_groups_history существовал до этой миграции - не трогаем
			if ($table !== 'lic_groups_history')
				$this->dropColumnIfExists($table, 'created_at');
		}
	}
}
