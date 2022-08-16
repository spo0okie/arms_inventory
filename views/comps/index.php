<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CompsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Comps::$titles;
$this->params['breadcrumbs'][] = \app\models\Comps::$titles;
$renderer=$this;
?>
<div class="comps-index">
	<?= DynaGridWidget::widget([
		'id' => 'comps-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Еще раз обращаю внимание, что это делать надо только для тех компьютеров, на которых не запускается автоматический скрипт!']),
		'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Comps','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>
</div>
