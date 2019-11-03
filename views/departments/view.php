<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Departments */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\Departments::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$deleteable=true; //тут переопределить возможность удаления элемента
?>
<div class="departments-view">

    <h1>
	    <?= Html::encode($this->title) ?>
	    <?= Html::a('<span class=\"glyphicon glyphicon-pencil\"></span>', ['update', 'id' => $model->id]) ?>
	    <?php  if($deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"></span>', ['users/delete', 'id' => $model->id], [
		    'data' => [
			    'confirm' => 'Удалить этот элемент?',
			    'method' => 'post',
		    ],
	    ]) ?>


    </h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'comment:ntext',
        ],
    ]) ?>

</div>
