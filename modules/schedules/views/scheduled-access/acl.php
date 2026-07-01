<?php

use app\models\Acls;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\modules\schedules\models\Schedules */

if (!isset($static_view)) $static_view=false;

//режим отображения: группировать ACL (по умолчанию) или показывать детально
$grouped=(bool)Yii::$app->request->get('group',1);

$rendered=[];
if ($grouped) {
	//группировка ACL по одинаковому набору ACE (в рамках одного расписания)
	foreach (Acls::groupBySignatures($model->acls) as $i=>$group) {
		$snames=array_map(static fn($a)=>$a->sname,$group);
		sort($snames,SORT_STRING|SORT_FLAG_CASE);
		$key=($snames[0]??'').'#'.$i;

		if (count($group)===1) {
			//одиночный ACL — обычная карточка
			$rendered[$key]=$this->render('@app/views/acls/card',['model'=>$group[0],'static_view'=>$static_view]);
		} else {
			//группа: один набор ACE над несколькими ресурсами
			$rendered[$key]=$this->render('@app/views/acls/card',['models'=>$group,'static_view'=>$static_view]);
		}
	}
} else {
	//детальный режим — каждый ACL отдельной карточкой (с индивидуальным редактированием)
	foreach ($model->acls as $i=>$acl) {
		$rendered[$acl->sname.'#'.$i]=$this->render('@app/views/acls/card',['model'=>$acl,'static_view'=>$static_view]);
	}
}
ksort($rendered,SORT_STRING|SORT_FLAG_CASE);
?>
<div class="schedules-acls">
	<div class="d-flex align-items-center justify-content-between">
		<h2>Доступы</h2>
		<?php if (!$static_view) { ?>
			<div class="btn-group btn-group-sm" role="group" title="Как показывать ACL расписания">
				<a class="btn <?= $grouped?'btn-primary':'btn-outline-primary' ?>" href="<?= Url::current(['group'=>null]) ?>">
					<span class="fas fa-layer-group"></span> Группировать
				</a>
				<a class="btn <?= !$grouped?'btn-primary':'btn-outline-primary' ?>" href="<?= Url::current(['group'=>0]) ?>">
					<span class="fas fa-list"></span> Детально
				</a>
			</div>
		<?php } ?>
	</div>
	<?= implode(' ',$rendered)?>
	<?= Html::a('Добавить',['/acls/create','Acls[schedules_id]'=>$model->id],['class'=>'btn btn-success pull-right'])?>
</div>
