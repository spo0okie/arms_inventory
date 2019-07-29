<?php

use yii\helpers\Html;
use yii\grid\GridView;


/* @var $this yii\web\View */
/* @var $models \app\models\Places */

\yii\helpers\Url::remember();
$this->title = \app\models\Places::$title;
$this->params['breadcrumbs'][] = $this->title;

echo $this->render('hdr_create_obj');
?>

<div class="places-index">

	<?php foreach ($models as $model) if (!is_object($model->parent)) {
		echo $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0]);
	} ?>
    <br />
	<?= Html::a('Добавить помещение первого уровня',['places/create']) ?>


</div>
