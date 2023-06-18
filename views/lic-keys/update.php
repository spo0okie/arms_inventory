<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */

$this->title = 'Редактирование лиц. ключа: ' . $model->keyShort;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$title, 'url' => ['lic-groups/index']];
$this->params['breadcrumbs'][] = ['label' => $model->licItem->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->licItem->lic_group_id]];
$this->params['breadcrumbs'][] = ['label' => $model->licItem->descr, 'url' => ['lic-items/view','id'=>$model->lic_items_id]];
$this->params['breadcrumbs'][] = ['label' => $model->keyShort, 'url' => ['lic-keys/view','id'=>$model->id]];
$this->params['breadcrumbs'][] = 'Обновление';
?>
<div class="lic-keys-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
