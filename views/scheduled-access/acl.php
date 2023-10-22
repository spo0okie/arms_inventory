<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$acls=[];
foreach ($model->acls as $acl)
	$acls[$acl->sname.count($acls)]=$this->render('/acls/card',['model'=>$acl]);

ksort($acls,SORT_STRING);
?>
<div class="schedules-acls">
	<h2>Доступы</h2>
	<?= implode(' ',$acls)?>
	<?= Html::a('Добавить',['acls/create','Acls[schedules_id]'=>$model->id],['class'=>'btn btn-success pull-right'])?>
</div>
