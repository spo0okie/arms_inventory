<?php

use app\helpers\StringHelper;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Domains */


$modelClass=get_class($model);
$classId=StringHelper::class2Id($modelClass);

$this->title = ($modelClass::$newItemPrefix??'Создание')
	.' '
	.mb_strtolower($modelClass::$title??'Объект');

//крошки [Список → заголовок] собираются автоматически в layout
//(см. views/layouts/main.php)
?>
<div class="<?= $classId ?>-create">

	<h1><?= Html::encode($this->title) ?></h1>
	
	<?= $this->render('/'.$classId.'/_form',['model'=>$model]) ?>

</div>