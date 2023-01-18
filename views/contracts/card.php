<?php

/* Карточка документа Можно использовать во View можно в тултипе */

use yii\helpers\Html;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */

$childs=    $model->childs;
$arms=      $model->arms;
$techs=     $model->techs;
$materials= $model->materials;
$lics=      $model->licItems;
$services=     $model->services;
//$phones=    $model->orgPhones;

$model_id=$model->id;

if (!isset($static_view)) $static_view=false;
$deletable=!(count($arms)||count($services)||count($lics)||count($childs)||count($techs));

?>

<h1>
    <?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'confirmMessage' => 'Действительно удалить этот документ?',
		'undeletableMessage'=>'Нельзя удалить этот документ, т.к. есть привязанные к нему объекты',
	]) ?>
</h1>

<h4>От: <?= $model->datePart ?><?= $this->render('item-state',compact('model'))?></h4>


<?php if ($model->total) { ?>
	<h4>
		Сумма: <?= $model->total.''.$model->currency->symbol ?>
		<?php if ($model->charge){ ?>
			(в т.ч. НДС: <?= $model->charge.''.$model->currency->symbol ?>)
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
                        success: function(res){window.location.reload();},
                        error: function(){alert('Error!');}
                    });
                    return false;
                });
JS;
        $this->registerJs($js);

        ?>
        Создать
        <a href="<?= \yii\helpers\Url::to(['/contracts/create','Contracts[parent_id]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Подчиненный документ</a>
        //
		<a href="<?= \yii\helpers\Url::to(['/arms/create','Arms[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">АРМ</a>
        //
		<a href="<?= \yii\helpers\Url::to(['/techs/create','Techs[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Оборудование</a>
        //
		<a href="<?= \yii\helpers\Url::to(['/materials/create','Materials[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Материалы</a>
        //
		<a href="<?= \yii\helpers\Url::to(['/lic-items/create','LicItems[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Лицензию</a>
        //
		<a href="<?= \yii\helpers\Url::to(['/services/create','Services[contracts_ids][]'=>$model->id])?>" class="open-in-modal-form" data-reload-page-on-submit="1">Услугу</a>
        :: на основании этого документа
    </p>
<?php } ?>

<br />



<?php if ($static_view) {

	if (count($childs)) { ?>
        <h4>Связанные документы:</h4>
        <p>
			<?php foreach ($childs as $child) {
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

<?php if (count($arms)) { ?>
    <h4>Прикреплен к АРМ:</h4>
    <p>
		<?php foreach ($arms as $arm) {
			$arm_id=$arm->id;
			echo $this->render('/arms/item',['model'=>$arm,'static_view'=>$static_view]);
			if (!$static_view) { ?>
                <a href="#"><span
                            class="fas fa-unlink href"
                            title="Отвязать документ от этого АРМа"
                            onclick="if (confirm('Отвязать документ от этого АРМа?')) $.ajax({
                                    url: '/web/contracts/unlink-arm?id=<?= $model->id ?>&arms_id=<?= $arm->id ?>',
                                    success: function(res) {
                                    location.reload()
                                    }
                                    })"
                    /></a>
				<?php
			}
			echo '<br/>';
		} ?>
    </p>
    <br />
<?php } ?>


<?php if (count($techs)) { ?>
    <h4>Прикреплен к оборудованию:</h4>
    <p>
		<?php foreach ($techs as $tech) {
			$tech_id=$tech->id;
			echo $this->render('/techs/item',['model'=>$tech,'static_view'=>$static_view]);
			if (!$static_view) { ?>
                <a href="#"><span
                            class="fas fa-unlink href"
                            title="Отвязать документ от этого оборудования"
                            onclick="if (confirm('Отвязать документ от этого оборудования?')) $.ajax({
                                url: '/web/contracts/unlink-tech?id=<?= $model->id ?>&techs_id=<?= $tech->id ?>',
                                success: function(res) {location.reload()}
                            })"
                    /></a>
				<?php
			}
			echo '<br/>';
		} ?>
    </p>
    <br />
<?php } ?>

<?php if (count($materials)) { ?>
    <h4>Прикреплен к поступлениям ЗиП и материалов:</h4>
    <p>
		<?php foreach ($materials as $material) {
			echo $this->render('/materials/item',['model'=>$material]).'<br />';
		} ?>
    </p>
    <br />
<?php } ?>


<?php if (count($lics)) { ?>
    <h4>Прикреплен к закупкам лицензий:</h4>
    <p>
		<?php foreach ($lics as $lic) {
			echo $this->render('/lic-items/item',['model'=>$lic]).'<br />';
		} ?>
    </p>
    <br />
<?php } ?>

<?php if (count($services)) { ?>
    <h4>Прикреплен к услугам:</h4>
    <p>
		<?php foreach ($services as $service) {
			echo $this->render('/services/item',['model'=>$service]).'<br />';
		} ?>
    </p>
    <br />
<?php } ?>

<?php if (is_array($partners=$model->partners)&&count($partners)) { ?>
    <h4>Контрагенты:</h4>
    <p>
    <?php foreach ($partners as $partner) {
        echo Html::a($partner->sname,['partners/view','id'=>$partner->id,'static_view'=>$static_view]);
    } ?>
    </p>
    <br />
<?php } else { ?>
    <h5><?= $model->partnersNames ?></h5>
    <br />
<?php } ?>


<h4>Комментарий:</h4>
<p>
<?= nl2br(htmlspecialchars($model->comment)) ?>
</p>

<br />

<h4>Сканы:</h4>
<div id="contract_<?= $model->id ?>_scans" class="scans-thumb-tiles">
    <?= $this->render('scans',['model'=>$model,'static_view'=>$static_view])?>
</div>

