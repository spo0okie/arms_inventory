<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetVlansSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = \app\models\NetVlans::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="net-vlans-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'net-vlans-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder'=>['networks_ids','name','domain_id','comment'],
		'createButton' => Html::a('Новый', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'gridOptions'=>['pjax' => true,],
		'filterModel' => $searchModel,
	]) ?>
</div>
