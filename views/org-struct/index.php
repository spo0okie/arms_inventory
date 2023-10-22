<?php

use app\models\OrgStruct;
use app\models\Partners;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models OrgStruct[] */
/* @var $org_id integer */
Url::remember();

$partner= Partners::findOne($org_id);

$this->title = OrgStruct::$titles;

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
			'tree_level'=>0
		]
	) ?>
</div>
