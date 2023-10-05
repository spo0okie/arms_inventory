<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\OrgStruct[] */
/* @var $org_id integer */
\yii\helpers\Url::remember();

$partner=\app\models\Partners::findOne($org_id);

$this->title = \app\models\OrgStruct::$titles;

$this->render('breadcrumbs',['partner'=>$partner,'model'=>null]);

$renderer=$this;
?>
<div class="org-struct-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать', ['create','OrgStruct[org_id]'=>$org_id], ['class' => 'btn btn-success']) ?>
    </p>

    <?= $this->render('tree-list',
		[
			'models'=>$models,
			'parent_id'=>null,
			'tree_level'=>0
		]
	) ?>
</div>
