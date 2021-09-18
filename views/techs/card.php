<?php

use yii\helpers\Html;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
$model_id=$model->id;
if (!isset($static_view)) $static_view=false;
if (!isset($no_model)) $no_model=false; //не выводить инфу о модели оборудования
$deleteable=!count($model->materialsUsages) && !count($model->contracts_ids);

?>
<h1>
    <?= Html::encode($model->num) ?>
    <?= Html::a('<span class="fas fa-pencil-alt"></span>', ['update', 'id' => $model->id]) ?>
    <?= $deleteable?Html::a('<span class="fas fa-trash"></span>', ['delete', 'id' => $model->id], [
        'data' => [
            'confirm' => 'Удалить оборудование из базы?',
            'method' => 'post',
        ],
    ]):'<span class="small">
			<span class="fas fa-lock" title="Нельзя удалить оборудование, к которому привязаны другие объекты. Для удаления сначала надо отвязать Материалы и Документы. (Если материалы действительно израсходованы на оборудование, то удалять его нельзя, надо поставить ему статус списано)"></span>
		</span>' ?>
</h1>

<?php if ($no_model) { ?>
<?php } else { ?>
	<?= $this->render('/tech-models/item',['model'=>$model->model,'long'=>1]) ?>
	
	<?php if ($model->model->individual_specs) { ?>
		<h4>Спецификация:</h4>
		<?= \Yii::$app->formatter->asNtext($model->specs) ?>
		<br />
	<?php } ?>
<?php } ?>


<p>
    <?php if (strlen($model->comment)){
        if ($model->isVoipPhone) {
            echo ('<h2>Внутренний номер: '.Yii::$app->formatter->asNtext($model->comment).'</h2>');
        } else echo (Yii::$app->formatter->asNtext($model->comment).'<br />');
    } ?>
    <?= \app\components\UrlListWidget::Widget(['list'=>$model->url,'ips'=>$model->ip]) ?>
</p>


<h4>Идентификаторы:</h4>
<p>
    Бухг. инв. №: <?= $model->inv_num ?> <br />
    Серийный №: <?= $model->sn ?>
</p>

<h4>Место установки и сотрудники:</h4>
<p>
    АРМ: <?= $this->render('/arms/item',['model'=>$model->arm]) ?> <br />
    Помещение: <?= $this->render('/places/item',['model'=>$model->effectivePlace]) ?> <br />
    Пользователь: <?= $this->render('/users/item',['model'=>$model->user]) ?> <br />
    Сотрудник ИТ: <?= $this->render('/users/item',['model'=>$model->itStaff]) ?> <br />
</p>

<?php if (count($model->services)) { ?>
	<h4>Учавствтует в работе сервисов:</h4>
	<p>
		<?php foreach ($model->services as $service) { ?>
			<?= $this->render('/services/item',['model'=>$service]) ?><br />
		<?php } ?>
	</p>
<?php } ?>


<?= $this->render('ips_list',compact('model')) ?>

<h4>MAC адрес(а)</h4>
<p>
	<?= Yii::$app->formatter->asNtext($model->formattedMac) ?>
</p>

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
    <?php foreach($model->materialsUsages as $materialsUsage) {
        echo $this->render('/materials-usages/item',['model'=>$materialsUsage,'material'=>true,'count'=>true,'date'=>true]).'<br />';
    } ?>
</p>

<h4>Заметки:</h4>
<?= Yii::$app->formatter->asNtext($model->history) ?><br />
