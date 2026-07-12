<?php

/* Карточка документа Можно использовать во View можно в тултипе */

use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use yii\helpers\Html;
use yii\helpers\Url;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

$model_id=$model->id;

if (!isset($static_view)) $static_view=false;

?>
<div class="d-flex flex-wrap flex-row-reverse">
	<div class="small opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></div>
	<div class="flex-fill"><h1>
		<?= LinkObjectWidget::widget([
			'model'=>$model,
			'confirmMessage' => 'Действительно удалить этот документ?',
			'undeletableMessage'=>'Нельзя удалить этот документ, т.к. есть привязанные к нему объекты',
		]) ?>
	</h1></div>
</div>

<?php /* шапка (дата+платёжный №+статус) и сумма (total+НДС+валюта) - составные блоки:
        значение собирается кастомной логикой, заголовок - renderCompositeTitle (ui-sources.md §3) */ ?>
<h4><?= ModelFieldWidget::renderCompositeTitle($model,['date','pay_id','state_id'],'От:','span') ?>
	<?= $model->datePart ?>

	<?= $model->pay_id?(' // '.Yii::$app->params['docs.pay_id.name'].':'.$model->pay_id):'' ?>

	<?= $this->render('item-state',compact('model'))?></h4>


<?php if ($model->total) { ?>
	<h4>
		<?= ModelFieldWidget::renderCompositeTitle($model,['total','charge','currency_id'],'Сумма:','span') ?>
		<?= \app\components\ModelFieldWidget::renderFieldValue($model,'total') ?>
		<?php if ($model->charge){ ?>
			(в т.ч. НДС: <?= \app\components\ModelFieldWidget::renderFieldValue($model,'charge') ?>)
		<?php } ?>
	</h4>
<?php } ?>

<?php if ($static_view) {
	foreach (['parent_id','successor'] as $linkAttr) {
		if ($row=ModelFieldWidget::renderFieldRow($model,$linkAttr,['item_options'=>['static_view'=>true]])) {
			echo Html::tag('h3',$row);
		}
	}
} ?>


<?php if (!$static_view) { ?>
    <p data-doc-anchor="create-from-doc">
        <?php
		
        $js = <<<JS
                $('#contracts-edit-form').on('afterSubmit', function(){window.location.reload();});
                $('#inet-edit-form').on('afterSubmit', function(){window.location.reload();});
                $('#phone-edit-form').on('afterSubmit', function(){window.location.reload();});
                $('#arms-form').on('beforeSubmit', function(){
                    var data = $(this).serialize();
                    $.ajax({
                        url: '/web/arms/create',
                        type: 'POST',
                        data: data,
                        success: function(){window.location.reload();},
                        error: function(){alert('Error!');}
                    });
                    return false;
                });
JS;
        $this->registerJs($js);

        ?>
        На основании этого документа создать:
        <a href="<?= Url::to(['/contracts/create','Contracts[parent_id]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">подчиненный документ</a>
        //
		<a href="<?= Url::to(['/techs/create','Techs[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">оборудование/АРМ</a>
        //
		<a href="<?= Url::to(['/materials/create','Materials[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">материалы</a>
        //
		<a href="<?= Url::to(['/lic-items/create','LicItems[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">лицензию</a>
        //
		<a href="<?= Url::to(['/services/create','Services[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">услугу</a>
    </p>
<?php } ?>

<br />



<?php if ($static_view) {
	echo ModelFieldWidget::widget([
		'model'=>$model, 'field'=>'children',
		'item_options'=>['static_view'=>$static_view],
		'card_options'=>['cardClass'=>'mb-3'],
	]);
} else {
    ?>
    <h4>Карта связей документов</h4>
        <?= $this->render('/contracts/tree-map',['model'=>$model,'show_payment'=>true]) ?>
    <br/>
<?php } ?>

<?php
    //отладка формирования цепочки связей
    //foreach ($model->successorsChain as $item) echo ModelWidget::widget(['model'=>$item]);
?>


<?php
echo ModelFieldWidget::widget([
	'model' => $model, 'field' => 'techs',
	'label' => 'Прикреплен к АРМ/оборудованию:',
	'item_options' => ['static_view' => $static_view, 'class'=>'text-nowrap','rc'=>true],
	'card_options' => ['cardClass' => 'mb-3'],
	//'lineBr'=> $static_view,
]);

echo ModelFieldWidget::widget([
	'model' => $model, 'field' => 'materials',
	'label' => 'Прикреплен к поступлениям ЗиП и материалов:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ModelFieldWidget::widget([
	'model' => $model, 'field' => 'licItems',
	'label' => 'Прикреплен к закупкам лицензий:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ModelFieldWidget::widget([
	'model' => $model, 'field' => 'services',
	'label' => 'Прикреплен к услугам:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ModelFieldWidget::widget([
	'model' => $model, 'field' => 'partners',
	'label' => 'Контрагенты:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ModelFieldWidget::widget([
	'model' => $model, 'field' => 'users',
	'label' => 'Пользователи:',
	'item_options' => ['static_view' => $static_view, 'noDelete'=>true],
	'card_options' => ['cardClass' => 'mb-3'],
]);
?>

<?php if (strlen(trim($model->comment??''))) { ?>
<?= ModelFieldWidget::renderFieldTitle($model,'comment') ?>
<p>
	<?= \app\components\ModelFieldWidget::renderFieldValue($model,'comment') ?>
</p>

<br />
<?php } ?>


<h4>Сканы:</h4>
<div id="contract_<?= $model->id ?>_scans" class="scans-thumb-tiles">
    <?= $this->render('scans',['model'=>$model,'static_view'=>$static_view])?>
</div>

<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>


