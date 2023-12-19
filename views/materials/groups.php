<?php

/*
 * Материалы по кучкам Помещение/Тип
 */

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\models\Materials;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $groupBy string */
$renderer=$this;
$this->title = Materials::$title;
$this->params['breadcrumbs'][] = $this->title;
$otherGroup=null;
$otherGroupName=null;
if ($groupBy=='type') {
	$otherGroup='name';
	$otherGroupName='Группировать по наименованию';
}
if ($groupBy=='name') {
	$otherGroup='type';
	$otherGroupName='Группировать по типу';
}





?>
<div class="materials-index">
	
	
	<?= DynaGridWidget::widget([
		'id' => 'materials-'.$groupBy.'-groups',
		'header' => Html::encode($this->title),
		'columns' => require 'groups-columns.php',
		'defaultOrder'=>['place','model','rest','count'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success'])
			.' // '.Html::a('Показать подробно',['index']+Yii::$app->request->get())
			.' // '.Html::a($otherGroupName,[$otherGroup.'-groups']+Yii::$app->request->get()),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\Materials','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
