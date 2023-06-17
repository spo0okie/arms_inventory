<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LicGroupsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\LicGroups::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="lic-groups-index">

	<?= DynaGridWidget::widget([
		'id' => 'lic-groups',
		'header' => Html::encode($this->title),
		'columns' => include 'columns.php',
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\LicGroups','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
	
</div>
