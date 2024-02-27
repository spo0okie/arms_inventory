<?php

use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$this->title = $model->sname;
$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;


$this->render('breadcrumbs',['model'=>$model,'static_view'=>true]);

YiiAsset::register($this);

?>
<div class="acls-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
