<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\OrgStruct[] */
\yii\helpers\Url::remember();

$this->title = \app\models\OrgStruct::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="org-struct-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= $this->render('tree-list',
		[
			'models'=>$models,
			'parent_id'=>null,
			'tree_level'=>0
		]
	) ?>
</div>
