<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
use app\components\DynaGridWidget;

\yii\helpers\Url::remember();

$this->title = \app\models\Techs::$armsTitles;
$this->params['breadcrumbs'][] = $this->title;
$this->params['layout-container'] = 'container-fluid';
$renderer = $this;
?>
<div class="arms-index">
	<?= /** @noinspection PhpIncludeInspection */
	DynaGridWidget::widget([
		'id' => 'arms-index',
		'header' => Html::encode($this->title),
		'columns' => require $this->findViewFile('/techs/columns'),
		'defaultOrder' => [
			'attach',
			'num',
			'model',
			'comp_id',
			'comp_hw',
			'ip',
			'mac',
			'state_id',
			'user',
			'user_position',
			'place',
			'inv_sn'
		],
		'createButton' => Html::a('Создать АРМ', ['/techs/create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=> '\app\models\Techs','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>


</div>
