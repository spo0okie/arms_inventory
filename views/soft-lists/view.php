<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = $model->descr;
//крошки собираются автоматически в layout (views/layouts/main.php)
$renderer=$this;
?>
<div class="soft-lists-view">

    <?= $this->render('card',['model'=>$model]) ?>

	<?= DynaGridWidget::widget([
		'id'=>'list-soft-index',
		'header'=>$this->title,
		'createButton'=>Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => include Yii::getAlias('@app').'/views/soft/columns.php',
		'defaultOrder' => ['descr','comment','hitsCount','compsCount'],
	]); ?>

</div>
