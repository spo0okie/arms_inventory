<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;
?>
<h4>
	Ответственный: <?= $this->render('/users/item',['model'=>$model->responsibleRecursive,'static_view'=>true]) ?>
</h4>
<?php if (is_array($model->supportRecursive) && count($model->supportRecursive)) { ?>
	<p>
		Поддержка:
		<?php
		$users=[];
		foreach ($model->supportRecursive as $user)
			$users[]=$this->render('/users/item',['model'=>$user,'static_view'=>true]);
		echo implode(", ",$users);
		?>
	</p>
	<br />
<?php }
if (is_object($model->infrastructureResponsibleRecursive)) { ?>
	<h4>
		Отв. за инфраструктуру: <?=
			$this->render('/users/item',['model'=>$model->infrastructureResponsibleRecursive,'static_view'=>true])
		?>
	</h4>
<?php }

if (is_array($model->infrastructureSupportRecursive) && count($model->infrastructureSupportRecursive)) { ?>
	<p>
		Поддержка инфраструктуры:
		<?php
		$users=[];
		foreach ($model->infrastructureSupportRecursive as $user)
			$users[]=$this->render('/users/item',['model'=>$user,'static_view'=>true]);
		echo implode(", ",$users);
		?>
	</p>
	<br />
<?php }
