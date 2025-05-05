<?php
/* @var $this yii\web\View */
/* @var $model app\models\ArmsModel */

use app\helpers\StringHelper;

$modelClass=get_class($model);
$classId=StringHelper::class2Id($modelClass);

?>
<div class="<?= $classId ?>-ttip ttip-card">
	<?= $this->render('/'.$classId.'/card',['model'=>$model,'static_view'=>true]) ?>
</div>
