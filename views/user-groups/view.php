<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\UserGroups::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="user-groups-view">
<?= $this->render('/user-groups/card',['model'=>$model]) ?>
</div>
