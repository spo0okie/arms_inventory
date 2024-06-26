<?php

use app\components\DynaGridWidget;
use app\models\Aces;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\AcesSearch */

$this->title = Aces::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="aces-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= DynaGridWidget::widget([
        'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
        'columns' => include 'columns.php',
    ]); ?>
</div>
