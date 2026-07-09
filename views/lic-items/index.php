<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;


/* @var $this yii\web\View */
/* @var $searchModel app\models\LicItemsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\LicItems::$titles;
//крошки собираются автоматически в layout (views/layouts/main.php)
$renderer=$this;
?>
<div class="lic-items-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'lic-items',
		'header' => Html::encode($this->title),
		'columns' => include 'columns.php',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
