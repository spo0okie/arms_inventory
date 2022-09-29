<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\grid\GridView;

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
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) .' // '.Html::a('По кучкам',['groups']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Materials','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
