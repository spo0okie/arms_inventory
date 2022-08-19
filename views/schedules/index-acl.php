<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Acls::$scheduleTitles;
$this->params['breadcrumbs'][] = $this->title;
$this->params['layout-container'] = 'container-fluid';

$renderer=$this;
?>
<div class="schedules-index">
	<?= DynaGridWidget::widget([
		'id' => 'schedules-acl-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns-acl.php',
		'defaultOrder' => [
			'objects',
			'resources',
			'name',
			'accessPeriods',
		],
		'createButton' => Html::a('Добавить', ['create-acl'], ['class' => 'btn btn-success']),
		//'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Arms','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>


</div>
