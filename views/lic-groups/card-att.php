<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */
/* @var $linksData \yii\data\ArrayDataProvider */

if (!isset($static_view)) $static_view=false;
//если не передать отдельно набор привязанных армов, то отрендерятся те что привязаны к группе
//можно передать АРМы конкретной закупки
if (!isset($arms)) $arms=$model->arms;
if (!isset($comps)) $comps=$model->comps;
if (!isset($users)) $users=$model->users;
if (!isset($licGroup)) $licGroup=$model;
if (!isset($unlink_href)) $unlink_href=['/lic-groups/unlink','id'=>$model->id];

$soft=$licGroup->soft;

?>

<?php if (!$static_view) { ?>
<div class="row">
    <div class="col-md-4">
		<?php }  ?>

        <h4>Лицензируемые продукты:</h4>
		<?php if (count($soft)) { ?>
        <p>
		    <?php foreach ($soft as $item) { ?>
			    <?= $this->render('/soft/item',['model'=>$item]) ?>
			    <?php if (!$static_view) {
					echo Html::a('<span class="fas fa-trash"/>', [
						'/lic-groups/unlink',
						'id' => $model->id,
						'soft_id' => $item->id,
					], [
						'data' => ['confirm' => 'Убрать ПО ' . $item->descr . ' из этой группы лицензий?',]
					]);
				}?>
			<br />
		    <?php } ?>
        </p>
		<?php } else { ?>

			<div class="alert-striped text-center w-100 p-2">
				<span class="fas fa-exclamation-triangle"></span>
					ОТСУТСТВУЮТ
				<span class="fas fa-exclamation-triangle"></span>
			</div>
		<?php } ?>

		<?php if (!$static_view) { ?>
    </div>
    <div class="col-md-8">
		<?php } else echo '<br />' ?>

        <h4>Привязки:</h4>
		<?php
		if (isset($linksData)) echo $this->render('/lic-links/obj-list', ['dataProvider' => $linksData]);
		
		if (!$static_view) { ?>
    </div>
</div>
<?php } else echo '<br />' ?>
