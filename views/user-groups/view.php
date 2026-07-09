<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)
\yii\web\YiiAsset::register($this);
?>
<div class="user-groups-view">
<?= ModelWidget::widget(['model'=>$model,'view'=>'card']) ?>
</div>


