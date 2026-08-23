<?php
namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Геометрия корпуса: tech_models.ports_layout.
 *
 * Порядок портов инвентаризация уже знает (шаблон модели и «порты фактически»
 * у экземпляра), но порядок — это ещё не корпус. Чтобы нарисовать карту
 * портов и ответить инженеру «где физически Gi1/0/13», нужно знать, сколько
 * на устройстве рядов, куда идёт нумерация и где отдельная грядка SFP.
 *
 * Геометрия объявляется у МОДЕЛИ, а не у экземпляра: передняя панель — это
 * свойство железа, у стекированного 2960X она та же самая, меняются только
 * имена портов. Поэтому одна запись обслуживает все экземпляры модели.
 */
class m260823_000002_tech_models_ports_layout extends ArmsMigration
{
	public function up()
	{
		$this->addColumnIfNotExists('tech_models', 'ports_layout', $this->text());
	}

	public function down()
	{
		$this->dropColumnIfExists('tech_models', 'ports_layout');
	}
}
