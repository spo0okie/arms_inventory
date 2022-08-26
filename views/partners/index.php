<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PartnersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Контрагенты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="partners-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'partners-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'createButton' => Html::a('Новый', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
