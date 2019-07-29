<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

$this->title = 'Внесение новой закупки';
if (!is_null($model->lic_group_id)) {
    //если группа лицензий указана, то опираемся на группц
	$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$title, 'url' => ['lic-groups/index']];
	$this->params['breadcrumbs'][] = ['label' => $model->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->lic_group_id]];
} else {
	$this->params['breadcrumbs'][] = ['label' => \app\models\LicItems::$title, 'url' => ['index']];
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lic-items-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
