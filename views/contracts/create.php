<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

if (!isset($modalParent)) $modalParent=null;
$this->title = 'Новый документ';
$this->params['breadcrumbs'][] = ['label' => \app\models\Contracts::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="contracts-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
		'modalParent' => $modalParent,
	]) ?>

</div>
<?php
//регистрируем обработчик "после сохранения" по умолчанию - перейти на страничку просмотра
$this->registerJs("$('#contracts-edit-form').on('afterSubmit',function() {contractFormGotoViewOnSave();})");
