<?php

/*
 * Материалы по кучкам Помещение/Тип
 */

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $groupBy string */
$renderer=$this;
$this->title = \app\models\Materials::$title;
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
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success'])
			.' // '.Html::a('Показать подробно',['index']+Yii::$app->request->get())
			.' // '.Html::a($otherGroupName,[$otherGroup.'-groups']+Yii::$app->request->get()),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Materials','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
