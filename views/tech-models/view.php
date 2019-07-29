<?php

use yii\helpers\Html;
use yii\widgets\DetailView;


/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

$techs=$model->techs;
$arms=$model->arms;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechModels::$title, 'url' => ['index']];
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
        <table class="places-arms-container">
		    <?php foreach ($techs as $tech ) { ?>
                <tr>
				    <?= $this->render('/techs/tdrow',['model'=>$tech]) ?>
                    <td class="arm_place"><?= is_object($tech->place)?$tech->place->fullName:'' ?></td>
                    <td>
	                    <?= Html::a('<span class="glyphicon glyphicon-trash"></span>', ['techs/delete', 'id' => $model->id], [
		                    'data' => [
			                    'confirm' => 'Действительно удалить этот экземпляр? Отменить потом не получится.',
			                    'method' => 'post',
		                    ],
	                    ]) ?>
                    </td>
                </tr>
		    <?php } ?>
        </table>

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
