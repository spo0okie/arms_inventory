<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = $model->descr;
$this->params['breadcrumbs'][] = ['label' => 'Списки ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="soft-lists-view">

    <?= $this->render('card',['model'=>$model]) ?>

	<?= DynaGridWidget::widget([
		'id'=>'list-soft-index',
		'header'=>$this->title,
		'createButton'=>Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/soft/columns.php',
		'defaultOrder' => ['descr','comment','hitsCount','compsCount'],
	]); ?>

</div>
