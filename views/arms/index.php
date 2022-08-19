<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
use app\components\DynaGridWidget;

\yii\helpers\Url::remember();

$this->title = \app\models\Arms::$titles;
$this->params['breadcrumbs'][] = $this->title;
$this->params['layout-container'] = 'container-fluid';
$renderer = $this;
?>
<div class="arms-index">
	<?= DynaGridWidget::widget([
		'id' => 'arms-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => [
			'attach',
			'num',
			'model_id',
			'comp_id',
			'comp_hw',
			'comp_ip',
			'comp_mac',
			'mac',
			'state_id',
			'user_id',
			'user_position',
			'places_id',
			'inv_sn'
		],
		'createButton' => Html::a('Создать АРМ', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Arms','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>


</div>
