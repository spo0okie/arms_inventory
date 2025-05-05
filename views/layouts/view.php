<?php

use app\helpers\StringHelper;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\ArmsModel */

$this->title = $model->name;

$modelClass=get_class($model);
$classId=StringHelper::class2Id($modelClass);

$indexTitle=$modelClass::$titles??$modelClass::$title??'Список';

$this->params['breadcrumbs'][] = ['label' => $indexTitle, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

YiiAsset::register($this);

?>
<div class="<?= $classId ?>-view">
	<?= $this->render('/'.$classId.'/card',['model'=>$model,'static_view'=>false]) ?>
</div>
