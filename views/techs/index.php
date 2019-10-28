<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;
$this->title = \app\models\Techs::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="techs-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

	<?= $this->render('/techs/table', [
		'searchModel' => $searchModel,
		'dataProvider' => $dataProvider,
		'columns'   => ['attach','num','model','sn','mac','ip','state','user','place','inv_num','comment'],
	]) ?>

</div>
