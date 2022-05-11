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
        <p>
		    <?php foreach ($soft as $item) { ?>
			    <?= $this->render('/soft/item',['model'=>$item]) ?>
			    <?php if (!$static_view) echo Html::a('<span class="fas fa-trash"/>',[
			        '/lic-groups/unlink',
                    'id'=>$model->id,
                    'soft_id'=>$item->id,
                ],[
				    'data'=>['confirm' => 'Убрать ПО '.$item->descr.' из этой группы лицензий?',]
                ]) ?>
                <br />
		    <?php } ?>
        </p>

		<?php if (!$static_view) { ?>
    </div>
    <div class="col-md-8">
		<?php } else echo '<br />' ?>

        <h4>Привязки:</h4>
		<?php /*
        <p>
			<?php foreach ($arms as $arm) {
				echo $this->render('/arms/item',['model'=>$arm,'icon'=>true,'static_view'=>true]);
				if (!$static_view) echo Html::a('<span class="fas fa-trash"/>',
					array_merge(
						['arms_id'=>$arm->id],
						$unlink_href
					),
					['data'=>['confirm' => 'Отвязать лицензию от АРМ '.$arm->num.'?',]]
				);
				echo '<br />';
			}
			foreach ($comps as $comp) {
				echo $this->render('/comps/item',['model'=>$comp,'icon'=>true,'static_view'=>true]);
				if (!$static_view) echo Html::a('<span class="fas fa-trash"/>',
					array_merge(
						['comps_id'=>$comp->id],
						$unlink_href
					),
					['data'=>['confirm' => 'Отвязать лицензию от OC '.$comp->name.'?',]]
				);
				echo '<br />';
			}
			foreach ($users as $user) {
				echo $this->render('/users/item',['model'=>$user,'icon'=>true,'static_view'=>true]);
				if (!$static_view) echo Html::a('<span class="fas fa-trash"/>',
					array_merge(
						['users_id'=>$user->id],
						$unlink_href
					),
					['data'=>['confirm' => 'Отвязать лицензию от пользователя '.$user->Ename.'?',]]
				);
				echo '<br />';
			} ?>
        </p>*/
		echo $this->render('/lic-links/obj-list', ['dataProvider' => $linksData]);
		
		if (!$static_view) { ?>
    </div>
</div>
<?php } else echo '<br />' ?>
