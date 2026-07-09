<?php

use app\helpers\StringHelper;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model \app\models\base\ArmsModel */

$this->title = $model->name;

$modelClass=get_class($model);
$classId=StringHelper::class2Id($modelClass);

//крошки [Список → карточка] собираются автоматически в layout из класса
//контроллера, действия и $this->title (см. views/layouts/main.php)

YiiAsset::register($this);

?>
<div class="<?= $classId ?>-view">
	<?= $this->render('/'.$classId.'/card',['model'=>$model,'static_view'=>false]) ?>
</div>
