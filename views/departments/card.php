<?php

use app\components\LinkObjectWidget;
use app\components\TextFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Departments */


?>

<h1>
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'confirmMessage' => 'Действительно удалить это подразделение?',
		'undeletableMessage'=>'Нельзя удалить это подразделение, т.к. есть привязанные к нему объекты',
	]) ?>
</h1>
<?= TextFieldWidget::widget(['model'=>$model,'field'=>'comment']) ?>