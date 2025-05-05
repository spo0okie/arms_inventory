<?php

use app\helpers\StringHelper;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Domains */


$modelClass=get_class($model);
$classId=StringHelper::class2Id($modelClass);

$indexTitle=$modelClass::$titles??$modelClass::$title??'Список';

$this->title = ($modelClass::$newItemPrefix??'Создание')
	.' '
	.mb_strtolower($modelClass::$title??'Объект');


$this->params['breadcrumbs'][] = ['label' => $indexTitle, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="<?= $classId ?>-create">

	<h1><?= Html::encode($this->title) ?></h1>
	
	<?= $this->render('/'.$classId.'/_form',['model'=>$model]) ?>

</div>