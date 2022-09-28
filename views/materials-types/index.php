<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\MaterialsTypes::$titles;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="materials-types-index">

	<?= DynaGridWidget::widget([
		'id' => 'materials-types-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\MaterialsTypes','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		//'filterModel' => $searchModel,
	]) ?>
</div>
