<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= "<?= " ?>Html::encode($model-><?= $generator->getNameAttribute() ?>) ?>
	<?= "<?= " ?>$static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>/update','id'=>$model->id])) ?>
	<?= "<?php " ?> if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>

<?= "<?= " ?>DetailView::widget([
    'model' => $model,
    'attributes' => [
<?php
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        echo "        '" . $name . "',\n";
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        echo "        '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
    }
}
?>
    ],
]) ?>

