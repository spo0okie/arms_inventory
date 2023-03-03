<?php

use yii\helpers\Html;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
$model_id=$model->id;
if (!isset($static_view)) $static_view=false;
if (!isset($no_model)) $no_model=false; //не выводить инфу о модели оборудования
$deleteable=!count($model->materialsUsages) && !count($model->contracts_ids);

if (is_object($model->state)) { ?>
	<span class="unit-status <?= $model->state->code ?> "><?= $model->state->name ?></span>
<?php }?>

<h1>
	<?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'confirmMessage' => 'Удалить этот оборудование из базы (необратимо)?',
		'undeletableMessage'=>'Нельзя удалить это оборудование/АРМ, т.к. есть привязанные к нему объекты.<br> Может лучше проставить флажок &quot;архивировано&quot;?',
	]) ?>
</h1>

<?php if (!$no_model) { ?>
	Модель: <?= $this->render('/tech-models/item',['model'=>$model->model,'long'=>1]) ?> <br />
	Серийный №: <?= $model->sn ?> <br />
	Бухг. инв. №: <?= $model->inv_num ?> <br />
	<?php if (strlen($model->comment)){
		echo ('<b>'.$model->commentLabel.':</b> '.Yii::$app->formatter->asNtext($model->comment).'<br />');
	} ?>
	
	<?php if ($model->model->individual_specs) { ?>
		<h4>Спецификация:</h4>
		<?= \Yii::$app->formatter->asNtext($model->specs) ?>
		<br />
	<?php } ?>
	
<?php } else { ?>
	<h4>Идентификаторы:</h4>
	<p>
		Серийный №: <?= $model->sn ?> <br />
		Бухг. инв. №: <?= $model->inv_num ?> <br />
		<?php if (strlen($model->comment)){
			echo ('<b>'.$model->commentLabel.':</b> '.Yii::$app->formatter->asNtext($model->comment).'<br />');
		} ?>
	</p>
<?php } ?>


<?php
	//для оборудования не АРМ выводим в список урл ссылку на IP устройства
	$urls=$model->url;
	$ips=$model->isComputer?'':$model->ip;
	if (strlen($urls.$ips)) { ?>
		<h4>Ссылки:</h4>
		<p>
			<?= \app\components\UrlListWidget::Widget(['list'=>$urls,'ips'=>$ips]) ?>
		</p>
<?php }	?>


<?php if (!$model->isComputer) echo $this->render('/arms/att-comps',['model'=>$model]) ?>


<h4>Место установки и сотрудники:</h4>
<p>
    <?= is_object($model->arm)?('АРМ: '.$this->render('/techs/item',['model'=>$model->arm]).'<br />'):'' ?>
    Помещение: <?= $this->render('/places/item',['model'=>$model->place]) ?> <br />
    Пользователь: <?= $this->render('/users/item',['model'=>$model->user]) ?> <br />
	<?= is_object($model->head)?('Руководитель отдела:'.$this->render('/users/item',['model'=>$model->head]).'<br/>'):'' ?>
	<?= is_object($model->itStaff)?('Сотрудник ИТ:'.$this->render('/users/item',['model'=>$model->itStaff]).'<br/>'):'' ?>
	<?= is_object($model->responsible)?('Ответственный:'.$this->render('/users/item',['model'=>$model->responsible]).'<br/>'):'' ?>
</p>

<?php if (count($model->services)) { ?>
	<h4>Участвует в работе сервисов:</h4>
	<p>
		<?php foreach ($model->services as $service) { ?>
			<?= $this->render('/services/item',['model'=>$service]) ?><br />
		<?php } ?>
	</p>
<?php } ?>

<div class="d-flex flex-row">
	<div class="pe-4">
		<?= $this->render('ips_list',compact('model')) ?>
	</div>
	<div class="pe-4">
		<h4>MAC адрес(а)</h4>
		<p>
			<?= Yii::$app->formatter->asNtext($model->formattedMac) ?>
		</p>
	</div>
</div>

<?php if (count($model->licItems) || count($model->licGroups) || count($model->licKeys)) {
	echo $this->render('/arms/att-lics',['model'=>$model]);
} ?>


<?php if (count($model->armTechs)) {
	echo $this->render('/arms/att-techs',['model'=>$model]);
} ?>

<h4>Документы:</h4>
<p>

    <?php if(is_array($contracts = $model->contracts) && count($contracts)) foreach ($contracts as $contract) {
        echo $this->render('/contracts/item',['model'=>$contract]).'<br />';
    } else { ?>
        отсутствуют<br />
    <?php }

    if (!$static_view) {
    //моздаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
        Modal::begin([
            'id'=>'tech_link_contract_modal',
			'size' => Modal::SIZE_LARGE,
            'title' => 'Выберите связанный с оборудованием документ'
        ]);
        echo $this->render('/contracts/_linkform');
        //закрываем форму
        Modal::end();

        Modal::begin([
            'id'=>'tech_new_contract_modal',
			'size' => Modal::SIZE_LARGE,
            'title' => '<h2>Добавление документа к оборудованию</h2>',
        ]);
        $contract=new \app\models\Contracts();
        $contract->techs_ids=[$model->id];
        echo $this->render('/contracts/_form',['model'=>$contract]);
        Modal::end();


        $js = <<<JS

            $('#tech_link_contract_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
            $('#tech_new_contract_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
            
            $('#contracts-link-form').on('beforeSubmit', function(){
                console.log($('input[name=contracts_id]').val());
                $.ajax({
                    url: '/web/contracts/link-tech',
                    type: 'GET',
                    data: {
                        techs_id: $model_id,
                        id: $('select[name=contracts_id]').val()
                    },
                    success: function(res){window.location.reload();},
                    error: function(){alert('Error!');}
                });
                return false;
            });
            
            $('#contracts-edit-form').on('afterSubmit', function(){window.location.reload();});
JS;

            $this->registerJs($js);

            ?>
        <a onclick="$('#tech_link_contract_modal').modal('toggle')" class="href">Привязать</a>
        /
        <a onclick="$('#tech_new_contract_modal').modal('toggle')" class="href">добавить новый</a>

    <?php } ?>

</p>

<?= $this->render('/acls/list',['models'=>$model->acls,'static_view'=>$static_view]) ?>

<h4>Использованные материалы:</h4>
<p>
    <?php
	$materialsUsages=$model->materialsUsages;
	\app\helpers\ArrayHelper::multisort($materialsUsages,'date',SORT_DESC);
	foreach($materialsUsages as $materialsUsage) {
        echo $this->render('/materials-usages/item',['model'=>$materialsUsage,'material'=>true,'count'=>true,'cost'=>true,'date'=>true]).'<br />';
    } ?>
</p>

<h4>Заметки:</h4>
<?= Yii::$app->formatter->asNtext($model->history) ?><br />
