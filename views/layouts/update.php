<?php

use app\helpers\StringHelper;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ArmsModels */


$modelClass=get_class($model);
$classId=StringHelper::class2Id($modelClass);

$indexTitle=$modelClass::$titles??$modelClass::$title??'Список';

$this->title = 'Редактирование : '.$model->name;


$this->params['breadcrumbs'][] = ['label' => $indexTitle, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view','id'=>$model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="<?= $classId ?>-update">

	<h1><?= Html::encode($this->title) ?></h1>

	<?= $this->render('/'.$classId.'/_form',['model'=>$model]) ?>

</div>