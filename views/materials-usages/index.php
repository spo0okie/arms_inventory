<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsUsagesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;

$this->title = \app\models\MaterialsUsages::$titles;
$this->params['breadcrumbs'][] = $this->title;




?>
<div class="materials-usages-index">
	<?= DynaGridWidget::widget([
		'id' => 'materials-usages-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		//'createButton' => Html::a('Добавить расход', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\MaterialsUsages','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
