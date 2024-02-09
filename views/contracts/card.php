<?php

/* Карточка документа Можно использовать во View можно в тултипе */

use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ListObjectsWidget;
use yii\helpers\Html;
use yii\helpers\Url;

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

<h4>От: <?= $model->datePart ?>
	
	<?= $model->pay_id?(' // '.Yii::$app->params['docs.pay_id.name'].':'.$model->pay_id):'' ?>
	
	<?= $this->render('item-state',compact('model'))?></h4>


<?php if ($model->total) { ?>
	<h4>
		Сумма: <?= number_format($model->total,2,'.',' ' ).$model->currency->symbol ?>
		<?php if ($model->charge){ ?>
			(в т.ч. НДС: <?= number_format($model->charge,2,'.',' ' ).$model->currency->symbol ?>)
		<?php } ?>
	</h4>
<?php } ?>

<?php if (!is_null($parent=$model->parent) && $static_view) { ?>
	<h3>Основной документ: <?= Html::a($parent->name,['view','id'=>$parent->id]) ?></h3>
<?php } ?>

<?php if (!is_null($sucessor=$model->successor) && $static_view) { ?>
    <h3>Замещен документом: <?= Html::a($sucessor->name,['view','id'=>$sucessor->id]) ?></h3>
<?php } ?>


<?php if (!$static_view) { ?>
    <p>
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
        Создать
        <a href="<?= Url::to(['/contracts/create','Contracts[parent_id]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Подчиненный документ</a>
        //
		<a href="<?= Url::to(['/techs/create','Techs[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Оборудование/АРМ</a>
        //
		<a href="<?= Url::to(['/materials/create','Materials[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Материалы</a>
        //
		<a href="<?= Url::to(['/lic-items/create','LicItems[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Лицензию</a>
        //
		<a href="<?= Url::to(['/services/create','Services[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Услугу</a>
        :: на основании этого документа
    </p>
<?php } ?>

<br />



<?php if ($static_view) {
	
	if (count($children=$model->childs)) { ?>
        <h4>Связанные документы:</h4>
        <p>
			<?php foreach ($children as $child) {
				echo $this->render('/contracts/item', ['model' => $child, 'static_view' => $static_view]) . '<br/>';
			} ?>
        </p>
        <br/>
		<?php
	}
} else {
    ?>
    <h4>Карта связей документов</h4>
        <?= $this->render('/contracts/tree-map',['model'=>$model,'show_payment'=>true]) ?>
    <br/>
<?php } ?>

<?php
    //отладка формирования цепочки связей
    //foreach ($model->successorsChain as $item) echo $this->render('/contracts/item',['model'=>$item]);
?>


<?php
echo ListObjectsWidget::widget([
	'models' => $model->techs,
	'title' => 'Прикреплен к АРМ/оборудованию:',
	'item_options' => ['static_view' => $static_view, 'class'=>'text-nowrap','rc'=>true],
	'card_options' => ['cardClass' => 'mb-3'],
	//'lineBr'=> $static_view,
]);

echo ListObjectsWidget::widget([
	'models' => $model->materials,
	'title' => 'Прикреплен к поступлениям ЗиП и материалов:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ListObjectsWidget::widget([
	'models' => $model->licItems,
	'title' => 'Прикреплен к закупкам лицензий:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ListObjectsWidget::widget([
	'models' => $model->services,
	'title' => 'Прикреплен к услугам:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ListObjectsWidget::widget([
	'models' => $model->partners,
	'title' => 'Контрагенты:',
	'item_options' => ['static_view' => $static_view, ],
	'card_options' => ['cardClass' => 'mb-3'],
]);

echo ListObjectsWidget::widget([
	'models' => $model->users,
	'title' => 'Пользователи:',
	'item_options' => ['static_view' => $static_view, 'noDelete'=>true],
	'card_options' => ['cardClass' => 'mb-3'],
]);
?>

<?php if (strlen(trim($model->comment))) { ?>
<h4>Комментарий:</h4>
<p>
	<?= nl2br(htmlspecialchars($model->comment)) ?>
</p>

<br />
<?php } ?>


<h4>Сканы:</h4>
<div id="contract_<?= $model->id ?>_scans" class="scans-thumb-tiles">
    <?= $this->render('scans',['model'=>$model,'static_view'=>$static_view])?>
</div>

<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
