<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$this->title = 'Программное обеспечение';
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
$manufacturers=\app\models\Manufacturers::fetchNames();
?>
<div class="soft-index">
    <?= \app\components\DynaGridWidget::widget([
		'id'=>'soft-index',
        'header'=>$this->title,
        'createButton'=>Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => include 'columns.php',
		'defaultOrder' => ['descr','comment','hits','comps'],
    ]); ?>
</div>
