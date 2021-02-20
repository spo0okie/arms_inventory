<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

if (!empty($model)) {
	if (!isset($name)) $name=$model-><?= $generator->getNameAttribute() ?>;
	?>

	<span class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-item"
		  qtip_ajxhrf="<?= "<?=" ?> \yii\helpers\Url::to(['<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>/ttip','id'=>$model->id]) ?>"
	>
		<?= "<?=" ?>  Html::a($name,['<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>/view','id'=>$model->id]) ?>
		<?= "<?=" ?>  Html::a('<span class="glyphicon glyphicon-pencil"></span>',['<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?= "<?php } ?>" ?>
