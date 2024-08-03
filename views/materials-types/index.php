<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\models\MaterialsTypes;
use app\models\MaterialsTypesSearch;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel MaterialsTypesSearch */

$this->title = MaterialsTypes::$titles;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="materials-types-index">

	<?= DynaGridWidget::widget([
		'id' => 'materials-types-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\MaterialsTypes','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
