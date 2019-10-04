<?php

use yii\helpers\Html;
use yii\grid\GridView;


/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

\yii\helpers\Url::remember();

$renderer=$this;
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index']];
$this->params['breadcrumbs'][] = ['label' => $model->type->name, 'url' => ['/tech-types/view','id'=>$model->type_id]];
$this->params['breadcrumbs'][] = $this->title;
$deletable=!count($model->arms)&&!count($model->techs);
?>
<div class="tech-models-view">

    <h1>
        <?= $this->render('/tech-types/item',['model'=>$model->type]) ?>
        <?= Html::encode($this->title) ?>
        <?= Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->id]) ?>
        <?php if ($deletable) echo Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Действительно удалить описание этой модели оборудования?',
		        'method' => 'post',
	        ],
        ]) ?>
    </h1>

    <?php if (!$deletable) { ?>
        <p>
            <span class="glyphicon glyphicon-warning-sign"></span> Описание этой модели оборудования нельзя удалить в настоящий момент, т.к. в БД есть экзепляры оборудования этой модели
        </p>
        <br />
    <?php } ?>

    <p>
	    <?= Yii::$app->formatter->asNtext($model->comment) ?>
    </p>

    <br />

    <p>
        <h4>Ссылки:</h4>
        <?= \app\components\UrlListWidget::Widget(['list'=>$model->links]) ?>
    </p>

    <br />

    <?php if (count($model->techs)) {?>
        <h4>Экземпляры оборудования:</h4>
	    <?= $this->render('/techs/table', [
		    'searchModel'   => $techSearchModel,
		    'dataProvider'  => $techDataProvider,
		    'columns'       => ['num','sn','mac','ip','state','user','place','inv_num'],
	    ]) ?>
    <?php } ?>

	<?php if (count($model->arms)) {?>
        <h4>АРМ этой модели:</h4>
		<?= $this->render('/arms/table', [
			'searchModel'   => $armsSearchModel,
			'dataProvider'  => $armsDataProvider,
			'columns'       => ['attach','num','model','comp_id','comp_ip','sn','state','user_id','places_id']
		]) ?>
	<?php } ?>

</div>
