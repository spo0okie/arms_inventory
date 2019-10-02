<?php

use yii\helpers\Html;
use yii\grid\GridView;


/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

$techs=$model->techs;
$arms=$model->arms;
$renderer=$this;
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index']];
$this->params['breadcrumbs'][] = ['label' => $model->type->name, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$deletable=!count($arms)&&!count($techs);
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

    <?php if (count($techs)) {?>
        <h4>Экземпляры оборудования:</h4>
	    <?= GridView::widget([
		    'dataProvider' => $dataProvider,
		    'filterModel' => $searchModel,
		    'columns' => [
			    //['class' => 'yii\grid\SerialColumn'],

			    //'id',
			    [
				    'attribute'=>'num',
				    'format'=>'raw',
				    'value' => function($data) use($renderer){
					    return $renderer->render('/techs/item',['model'=>$data]);
				    }
			    ],
			    /*[
				    'attribute'=>'model',
				    'format'=>'raw',
				    'value' => function($data) use($renderer){
					    return is_object($data->model)?$renderer->render('/tech-models/item',['model'=>$data->model,'long'=>true]):null;
				    }
			    ],*/
			    'sn',
			    'inv_num',
			    [
				    'attribute'=>'place',
				    'format'=>'raw',
				    'value' => function($data) use($renderer){
					    return $renderer->render('/places/item',['model'=>$data->effectivePlace,'full'=>true]);
				    }
			    ],
			    'user.Ename',
			    //'it_staff_id',
			    //'comment',
			    'mac',
			    'ip',
			    //'url:ntext',

			    //['class' => 'yii\grid\ActionColumn'],
		    ],
	    ]); ?>

    <?php } ?>

	<?php if (count($arms)) {?>
        <h4>АРМ этой модели:</h4>
        <table class="places-arms-container">
			<?php foreach ($arms as $arm ) { ?>
                <tr>
					<?= $this->render('/arms/tdrow',['model'=>$arm,'skip'=>['arm_model']]) ?>
                    <td class="arm_place"><?= is_object($arm->place)?$arm->place->fullName:'' ?></td>
                </tr>
			<?php } ?>
        </table>
	<?php } ?>

</div>
