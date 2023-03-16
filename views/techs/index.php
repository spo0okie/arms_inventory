<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;


\yii\helpers\Url::remember();

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;
$this->title = \app\models\Techs::$title;
$this->params['breadcrumbs'][] = $this->title;
$this->params['layout-container'] = 'container-fluid';

?>
<div class="techs-index">

	<?= DynaGridWidget::widget([
		'id' => 'techs-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['attach','num','model','sn','mac','ip','state','user','place','inv_num','comment'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Techs','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>

</div>
