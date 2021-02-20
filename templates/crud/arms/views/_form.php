<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

/* @var $model \yii\db\ActiveRecord */
$model = new $generator->modelClass();
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
    $safeAttributes = $model->attributes();
}

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form">

    <?= "<?php " ?>$form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => '<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>/validate']:	//для новых моделей
			//['<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>/validate','id'=>$model->id], //для существующих
	]); ?>

<?php foreach ($generator->getColumnNames() as $attribute) {
    if (in_array($attribute, $safeAttributes)) {
    	if ((strlen($attribute)>3) && substr($attribute,strlen($attribute)-3,3)=='_id') {
			echo "    <?= \$form->field(\$model, '$attribute')->widget(Select2::className(), [\n";
			echo "        'data' => ".ltrim($generator->modelClass, '\\')."::fetchNames(),\n";
			echo "        'options' => [\n";
			echo "            'placeholder' => 'Выберите '.".ltrim($generator->modelClass, '\\')."::\$title,\n";
			echo "        ],\n";
			echo "        'pluginOptions' => [\n";
			echo "            'allowClear' => true,\n";
			echo "            'multiple' => false\n";
			echo "        ]\n";
			echo "    ]) ?>\n";
		} else echo "    <?= " . $generator->generateActiveField($attribute) . " ?>\n\n";
    }
} ?>
    <div class="form-group">
        <?= "<?= " ?>Html::submitButton(<?= $generator->generateString('Save') ?>, ['class' => 'btn btn-success']) ?>
    </div>

    <?= "<?php " ?>ActiveForm::end(); ?>

</div>
