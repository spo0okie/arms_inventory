<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer=$this;
$this->title = \app\models\Materials::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="materials-index">
	
	
	<?= DynaGridWidget::widget([
		'id' => 'materials-types-groups',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success'])
			.' // '.Html::a('Группировать по наименованию',['name-groups']+Yii::$app->request->get())
			.' // '.Html::a('Группировать по типу',['type-groups']+Yii::$app->request->get()),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Materials','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
