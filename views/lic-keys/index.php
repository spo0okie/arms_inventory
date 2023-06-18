<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\LicKeysSearch */

$this->title = \app\models\LicKeys::$title;
$this->params['breadcrumbs'][] = $this->title;

$renderer=$this;
?>
<div class="lic-keys-index">
	
	<?= DynaGridWidget::widget([
		'id' => 'lic-keys',
		'header' => Html::encode($this->title),
		'columns' => include 'columns.php',
		//'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\LicKeys','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
