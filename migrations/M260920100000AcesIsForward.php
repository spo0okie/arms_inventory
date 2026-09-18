<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;
use yii\db\Query;

/**
 * Признак проброса переезжает с типа доступа на запись доступа:
 * access_types.is_forward → aces.is_forward (plans/access-chains.md, итерация 2, правка по обкатке).
 *
 * Флаг на типе заставлял заводить копию каждого типа доступа («HTTPS» → «HTTPS forward»,
 * «RDP» → «RDP forward»…): проброс — ось, ортогональная протоколу, и перемножать их в
 * одном справочнике — загромождать реестр типов. Проброс — свойство ХОПА, как и
 * указатели транзита, поэтому живёт на ACE: запись «белый IP → узел, HTTPS,
 * TCP 443->8443, проброс» использует обычный тип HTTPS.
 *
 * Перенос данных: записи доступа, у которых был форвард-тип (сам с флагом либо
 * включающий такой тип через иерархию), получают aces.is_forward=1. Сами типы-копии
 * миграция не трогает: слить «HTTPS forward» с «HTTPS» автоматически надёжно нельзя —
 * список бывших форвард-типов выводится в лог, их перевыбирают в записях и удаляют руками.
 */
class M260920100000AcesIsForward extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('aces', 'is_forward', $this->boolean()->notNull()->defaultValue(false));
		$this->addColumnIfNotExists('aces_history', 'is_forward', $this->boolean()->null());

		$types=$this->db->getTableSchema('access_types',true);
		if (isset($types->columns['is_forward'])) {
			//форвард-типы с учётом иерархии: тип, включающий проброс, сам был пробросом
			$ids=array_map('intval',(new Query())->select('id')->from('access_types')
				->where(['is_forward'=>1])->column($this->db));
			do {
				$parents=count($ids)?array_map('intval',(new Query())->select('parent_id')->from('access_types_hierarchy')
					->where(['child_id'=>$ids])->andWhere(['not in','parent_id',$ids])->column($this->db)):[];
				$ids=array_values(array_unique(array_merge($ids,$parents)));
			} while (count($parents));

			if (count($ids)) {
				$aces=array_map('intval',(new Query())->select('aces_id')->distinct()->from('access_in_aces')
					->where(['access_types_id'=>$ids])->column($this->db));
				if (count($aces)) $this->update('aces',['is_forward'=>1],['id'=>$aces]);

				$names=(new Query())->select(['id','name'])->from('access_types')->where(['id'=>$ids])->all($this->db);
				echo "    > перенесён признак проброса на записей доступа: ".count($aces)."\n";
				echo "    > бывшие форвард-типы (копии можно перевыбрать в записях и удалить):\n";
				foreach ($names as $row) echo "        #{$row['id']} {$row['name']}\n";
			}

			$this->dropColumn('access_types','is_forward');
		}
	}

	/**
	 * {@inheritdoc}
	 * Возвращает колонку типам (пустую: какие типы были пробросом, уже не восстановить)
	 */
	public function down()
	{
		$this->addColumnIfNotExists('access_types', 'is_forward', $this->boolean()->defaultValue(false));
		$this->dropColumnIfExists('aces_history', 'is_forward');
		$this->dropColumnIfExists('aces', 'is_forward');
	}
}
