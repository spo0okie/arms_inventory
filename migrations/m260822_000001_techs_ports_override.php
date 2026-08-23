<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Порты конкретного устройства: techs.ports_override.
 *
 * Порядок портов объявляется текстом (строка = порт), и до сих пор такое
 * объявление было только у модели оборудования. Но у экземпляра имена
 * расходятся с модельными: после стекирования Gi0/13 становится Gi2/0/13, а
 * на MikroTik интерфейсы переименовывают руками. Пока раскладки экземпляра
 * нет, карточка рисует фантомные порты модели, и на вопрос «где физически
 * порт Gi2/0/13» ответить нечем.
 *
 * Колонка называется не `ports`: у Techs есть связь getPorts(), а в Yii
 * атрибут перекрывает связь — колонка `techs.ports` молча сломала бы
 * $tech->ports во всех существующих местах.
 *
 * Зеркало истории (techs_history) повторяет колонки мастер-таблицы, поэтому
 * колонка заводится и там — иначе журнал изменений её не заметит.
 */
class m260822_000001_techs_ports_override extends ArmsMigration
{
	public function up()
	{
		$this->addColumnIfNotExists('techs', 'ports_override', $this->text());
		$this->addColumnIfNotExists('techs_history', 'ports_override', $this->text());
	}

	public function down()
	{
		$this->dropColumnIfExists('techs_history', 'ports_override');
		$this->dropColumnIfExists('techs', 'ports_override');
	}
}
