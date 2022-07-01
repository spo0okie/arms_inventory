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
    <?= Html::encode($model->name) ?>
	<?php if (!$static_view) {
		echo Html::a('<span class="fas fa-pencil-alt"></span>', ['update', 'id' => $model->id], ['title' => 'Изменить',]);
		if ($deletable) echo Html::a('<span class="fas fa-trash"></span>', ['delete', 'id' => $model->id], [
			'title' => 'Удалить',
			'data' => [
				'confirm' => 'Удалить этот документ и все его сканы? Данное действие необратимо.',
				'method' => 'post',
			],
		]); else { ?>
			<span class="small">
				<span class="fas fa-lock"
					  title="Невозможно удалить этот документ, т.к. есть привязанные к нему объекты"></span>
			</span>
		<?php }
	}?>
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

        //создание связаного документа
        Modal::begin(['title' => '<h2>Добавление документа</h2>','size'=>Modal::SIZE_LARGE,'options'=>[
				'id'=>'contracts_add_modal',
				'tabindex' => false
			]]);
            $contractModel=new \app\models\Contracts();
            $contractModel->parent_id=$model_id;
            $contractModel->partners_ids=$model->partners_ids;
            echo $this->render('/contracts/_form',['model'=>$contractModel,'modalParent'=>'#contracts_add_modal']);
        Modal::end();

        //создание связанного АРМ
        Modal::begin(['title' => '<h2>Добавление АРМ</h2>','size'=>Modal::SIZE_LARGE,'options'=>[
			'id'=>'arms_add_modal',
			'tabindex' => false
		]]);
            $armModel=new \app\models\Arms();
            $armModel->contracts_ids=[$model_id];
            echo $this->render('/arms/_form',['model'=>$armModel,'modalParent'=>'#arms_add_modal']);
        Modal::end();

        //создание связанного оборудования
        Modal::begin(['title' => '<h2>Добавление оборудования</h2>','size'=>Modal::SIZE_LARGE,'options'=>[
			'id'=>'techs_add_modal',
			'tabindex' => false
		]]);
        $techModel=new \app\models\Techs();
        $techModel->contracts_ids=[$model_id];
        echo $this->render('/techs/_form',['model'=>$techModel,'modalParent'=>'#techs_add_modal']);
        Modal::end();

        //создание связанного оборудования
        Modal::begin(['title' => '<h2>Добавление материалов</h2>','size'=>Modal::SIZE_LARGE,'options'=>[
			'id'=>'materials_add_modal',
			'tabindex' => false
		]]);
        $materialsModel=new \app\models\Materials();
        $materialsModel->contracts_ids=[$model_id];
        echo $this->render('/materials/_form',['model'=>$materialsModel,'modalParent'=>'#materials_add_modal']);
        Modal::end();

        //создание связанной лицензии
        Modal::begin(['title' => '<h2>Добавление лицензии</h2>','size'=>Modal::SIZE_LARGE,'options'=>[
			'id'=>'lic_add_modal',
			'tabindex' => false
		]]);
        $licModel=new \app\models\LicItems();
        $licModel->contracts_ids=[$model_id];
        echo $this->render('/lic-items/_form',['model'=>$licModel,'modalParent'=>'#lic_add_modal']);
        Modal::end();

        //создание связанного оборудования
        /*Modal::begin(['title' => '<h2>Добавление ввода интернет</h2>','size'=>Modal::SIZE_LARGE,'options'=>[
			'id'=>'inet_add_modal',
			'tabindex' => false
		]]);
        $inetModel=new \app\models\OrgInet();
        $inetModel->contracts_id=[$model_id];
        echo $this->render('/org-inet/_form',['model'=>$inetModel,'modalParent'=>'#inet_add_modal']);
        Modal::end();

        //создание связанного оборудования
        Modal::begin(['title' => '<h2>Добавление городского тел. номера</h2>','size'=>Modal::SIZE_LARGE,'options'=>[
			'id'=>'phone_add_modal',
			'tabindex' => false
		]]);
        $phoneModel=new \app\models\OrgPhones();
        $phoneModel->contracts_id=[$model_id];
        echo $this->render('/org-phones/_form',['model'=>$phoneModel,'modalParent'=>'#phone_add_modal']);
        Modal::end();*/

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
        <a onclick="$('#contracts_add_modal').modal('toggle')" class="href">Подчиненный документ</a>
        //
        <a onclick="$('#arms_add_modal').modal('toggle')" class="href">АРМ</a>
        //
        <a onclick="$('#techs_add_modal').modal('toggle')" class="href">Оборудование</a>
        //
        <a onclick="$('#materials_add_modal').modal('toggle')" class="href">Материалы</a>
        //
        <a onclick="$('#lic_add_modal').modal('toggle')" class="href">Лицензию</a>
        //
        <a onclick="$('#service_add_modal').modal('toggle')" class="href">Услугу</a>
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
    <p>
        <?= $this->render('/contracts/tree-map',['model'=>$model]) ?>
    </p>
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
			echo $this->render('/services/item',['model'=>$service]);
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

