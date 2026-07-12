<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsUsagesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;

$this->title = \app\models\MaterialsUsages::$titles;
//крошки собираются автоматически в layout (views/layouts/main.php)




?>
<div class="materials-usages-index">
	<div class="alert alert-info py-2">
		Расход вносится с карточки конкретного материала кнопкой «Использовать»
		(см. <?= Html::a('ЗиП и Материалы', ['/materials/index']) ?>),
		поэтому кнопки добавления здесь нет.
	</div>
	<?= DynaGridWidget::widget([
		'id' => 'materials-usages-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		//'createButton' => Html::a('Добавить расход', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
