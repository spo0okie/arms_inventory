<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\models\Materials;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer=$this;
$this->title = Materials::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="materials-index">
	
	
	<?= DynaGridWidget::widget([
		'id' => 'materials-types-groups',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder'=>['place','model','comment','date','rest','count'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success'])
			.' // '.Html::a('Группировать по наименованию',['name-groups']+Yii::$app->request->get())
			.' // '.Html::a('Группировать по типу',['type-groups']+Yii::$app->request->get()),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\Materials','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
