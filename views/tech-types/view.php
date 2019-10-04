<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */
\yii\helpers\Url::remember();

$this->title = $model->name;
$techModels=$model->techModels;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tech-types-view">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $model->id]) ?>
        <?= !count($techModels)?Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Удалить этот тип оборудования?',
		        'method' => 'post',
	        ],
        ]):'' ?>
    </h1>

    <?php if (count($techModels)) { ?>
        <p>
            <span class="glyphicon glyphicon-warning-sign"></span> Невозможно удалить этот тип оборудования, т.к. заведены модели оборудования этого типа. (см ниже)
        </p>
    <?php } ?>


    <div class="row">
        <div class="col-md-6">
            <h4>Модели оборудования</h4>
            <table class="table table-bordered">
                <tr>
                    <th>Модель</th>
                    <th>Кол-во экз.</th>
                </tr>
	        <?php foreach ($techModels as $techModel) { ?>
                <tr>
                    <td>
	                    <?= $this->render('/tech-models/item',['model'=>$techModel,'long'=>true]) ?><br />
                    </td>
                    <td>
                        <?= $techModel->usages ?>
                    </td>
                </tr>
	        <?php } ?>

            </table>

            <?php
	        Modal::begin([
		        'id'=>'tech_models-add',
		        'header' => '<h2>Добавление модели оборудования</h2>',
		        'toggleButton' => [
			        'label' => 'Добавить модель',
			        'tag' => 'a',
			        'class' => 'btn btn-success',
		        ],
	        ]);

	        $techModel=new \app\models\TechModels();
	        $techModel->type_id=$model->id;

	        echo $this->render(
		        '/tech-models/_form',
		        [
			        'model'=>$techModel,
		        ]
	        );
	        $js = <<<JS
    $('#tech_models-add').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
    $('#tech_models-edit-form').on('beforeSubmit', function(){
        var data = $(this).serialize();
        $.ajax({
            url: '/web/tech-models/create',
            type: 'POST',
            data: data,
            success: function(res){
                //alert();
                window.location.reload();// replace(window.location.toString()+'&manufacturers_id='+res[0].id);
            },
            error: function(){
                alert('Error!');
            }
        });
        return false;
    });
JS;
	        $this->registerJs($js);
	        Modal::end();
	        ?>

        </div>
        <div class="col-md-6">
            <h4><?= $model->getAttributeLabel('comment')?></h4>
            <i><?= $model->getAttributeHint('comment')?></i>
            <p>
		        <?= Yii::$app->formatter->asNtext($model->comment) ?>
            </p>
            <br />

            <h4><?= $model->getAttributeLabel('code')?></h4>
            <i><?= $model->getAttributeHint('code')?></i>
            <p>
		        <?= $model->code ?>
            </p>

        </div>
    </div>

    <br />

    <?php if (\app\models\TechTypes::isPC($model->id)) { ?>
        <h4>Экземпляры рабочих мест</h4>
	    <?= $this->render('/arms/table', [
		    'searchModel' => $armsSearchModel,
		    'dataProvider' => $armsDataProvider,
		    //'columns'   => ['attach','num','model','sn','mac','ip','state','user','place','inv_num'],
	    ]) ?>
    <?php } else { ?>
        <h4>Экземпляры оборудования</h4>

        <?= $this->render('/techs/table', [
            'searchModel' => $techsSearchModel,
            'dataProvider' => $techsDataProvider,
            'columns'   => ['attach','num','model','sn','mac','ip','state','user','place','inv_num'],
        ]) ?>
    <?php } ?>


</div>
