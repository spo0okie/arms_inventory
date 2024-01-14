<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use app\components\ModelFieldWidget;
use app\components\LinkObjectWidget;


/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= "<?= " ?> LinkObjectWidget::widget([
		'model'=>$model,
		//'confirmMessage' => 'Действительно удалить этот документ?',
		//'undeletableMessage'=>'Нельзя удалить этот документ, т.к. есть привязанные к нему объекты',
	]) <?= "?>" ?>
</h1>
<?= "<?php " ?>
<?php foreach ($generator->getColumnNames() as $name) { ?>
	echo ModelFieldWidget::widget(['model'=>$model,'field'=>'<?= $name ?>']);
<?php } ?>

