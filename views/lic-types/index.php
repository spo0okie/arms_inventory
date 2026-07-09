<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\LicTypesSearch */

$renderer=$this;

$this->title = \app\models\LicTypes::$titles;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="lic-types-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'lic-types',
		'header' => Html::encode($this->title),
		'columns' => include 'columns.php',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
