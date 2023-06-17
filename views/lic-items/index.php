<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;


/* @var $this yii\web\View */
/* @var $searchModel app\models\LicItemsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\LicItems::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="lic-items-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'lic-items',
		'header' => Html::encode($this->title),
		'columns' => include 'columns.php',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\LicItems','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
