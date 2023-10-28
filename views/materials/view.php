<?php


/* @var $this yii\web\View */
/* @var $model app\models\Materials */

use app\models\Materials;
use yii\helpers\Url;
use yii\web\YiiAsset;

Url::remember();
$this->title =  $model->type->name.': '. $model->model;

$this->params['breadcrumbs'][] = ['label' => Materials::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

YiiAsset::register($this);

?>
<div class="materials-view">

	<?= $this->render('card',['model'=>$model]) ?>


</div>
