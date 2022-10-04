<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsUsagesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;

$this->title = \app\models\MaterialsUsages::$titles;
$this->params['breadcrumbs'][] = $this->title;


//собираем суммы по валютам
$totals=[];
$charge=[];
foreach ($dataProvider->models as $model) {
	/**
	 * @var \app\models\MaterialsUsages $model
	 */
	
	if ($model->cost) {
		if (!isset($totals[$model->material->currency_id])) $totals[$model->material->currency_id]=0;
		if (!isset($charge[$model->material->currency_id])) $charge[$model->material->currency_id]=0;
		
		$totals[$model->material->currency_id]+=$model->cost;
		$charge[$model->material->currency_id]+=$model->charge;
	}
	
}

$arrFooter=['total'=>[],'charge'=>[]];
foreach (\app\models\Currency::find()->all() as $currency) {
	/**
	 * @var \app\models\Currency $currency
	 */
	if (isset($totals[$currency->id]) && $totals[$currency->id]) {
		$arrFooter['total'][]=number_format($totals[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
	if (isset($charge[$currency->id]) && $charge[$currency->id]) {
		$arrFooter['charge'][]=number_format($charge[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
}

?>
<div class="materials-usages-index">
	<?= DynaGridWidget::widget([
		'id' => 'materials-usages-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		//'createButton' => Html::a('Добавить расход', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\MaterialsUsages','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
