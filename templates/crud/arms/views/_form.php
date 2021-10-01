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

$tableSchema = $model->getTableSchema();
$text_areas=[];

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
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
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>

<?php foreach ($generator->getColumnNames() as $attribute) {
	if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
		$attribute_type=null;
	} else {
		$attribute_type=$tableSchema->columns[$attribute]->type;
	}
    if (in_array($attribute, $safeAttributes)) {
		$relation=null;
    	if ((strlen($attribute)>3) && substr($attribute,strlen($attribute)-3,3)=='_id') {
			try {
				$relation=$model->getRelation(Inflector::variablize(substr($attribute,0,strlen($attribute)-3)));
			} catch (Exception $e) {
				try {
					$relation=$model->getRelation(Inflector::variablize(substr($attribute,0,strlen($attribute)-4)));
				} catch (Exception $e) {}
			}
		}
    	if (is_object($relation)) {
			echo "    <?= \$form->field(\$model, '$attribute')->widget(Select2::className(), [\n";
			echo "        'data' => ".ltrim($relation->modelClass, '\\')."::fetchNames(),\n";
			echo "        'options' => [\n";
			echo "            'placeholder' => 'Выберите '.\$model->getAttributeLabel('$attribute') \n";
			echo "        ],\n";
			echo "        'pluginOptions' => [\n";
			echo "            'allowClear' => true,\n";
			echo "            'multiple' => false\n";
			echo "        ]\n";
			echo "    ]) ?>\n";
		} elseif ($attribute_type=="text") {
    		echo "<?= \$form->field(\$model, '$attribute')->textarea(['rows' => max(4, count(explode(\"\\n\", \$model->$attribute)))]) ?>";
    		$text_areas[]=$attribute;
		} else echo "    <?= " . $generator->generateActiveField($attribute) . " ?>\n\n";
	
	
	}
} ?>
		
	<?php
		$text_areas_code=[];
		foreach ($text_areas as $area) {
			$text_areas_code[]="$('#". Inflector::camel2id(StringHelper::basename($generator->modelClass)) ."-$area').autoResize();";
		}
		if (count($text_areas_code)) {
			echo '<?php $this->registerJs("'.implode(';',$text_areas_code).'"); ?>';
		}
	?>


    <div class="form-group">
        <?= "<?= " ?>Html::submitButton(<?= $generator->generateString('Save') ?>, ['class' => 'btn btn-success']) ?>
    </div>

    <?= "<?php " ?>ActiveForm::end(); ?>

</div>
