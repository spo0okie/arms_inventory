<?php

use app\components\DynaGridWidget;
use app\models\Acls;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\AclsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
Url::remember();

$this->title = Acls::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="acls-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
	
	<?= DynaGridWidget::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => include 'columns.php',
	]); ?>
</div>
