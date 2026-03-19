<?php

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

//\yii\helpers\Url::remember();

use app\components\widgets\page\ModelWidget;
use yii\web\YiiAsset;

$this->title = $model->sname;
if (is_object($model->acl))
	ModelWidget::widget(['model'=>$model->acl,'static_view'=>false, 'view'=>'breadcrumbs']);
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

?>
<div class="aces-view">
	<?= ModelWidget::widget(['model'=>$model,'view'=>'card']) ?>
</div>
