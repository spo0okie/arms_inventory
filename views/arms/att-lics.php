<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */

use yii\helpers\Html;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */
    $licGroups=$model->licGroups;
    $licItems=$model->licItems;
    $licKeys=$model->licKeys;
    if (!isset($static_view)) $static_view=false;
    $model_id=$model->id;
?>

<h4>Назначенные лицензии:</h4>
<div id="arms_<?= $model->id ?>_attached_lics">
	<?php
	if (count($licKeys) || count($licItems) || count ($licGroups)) {
		foreach ($licKeys as $key) { ?>
			<?= $this->render('/lic-keys/item',['model'=>$key,'static_view'=>$static_view,'name'=>$key->sname]) ?><br />
		<?php }
		foreach ($licItems as $item) { ?>
			<?= $this->render('/lic-items/item',['model'=>$item,'static_view'=>$static_view,'name'=>$item->sname]) ?><br />
		<?php }
		foreach ($licGroups as $group) { ?>
			<?= $this->render('/lic-groups/item',['model'=>$group,'static_view'=>$static_view]) ?><br />
		<?php }
	} else { ?>
        отсутствуют
	<?php }?>
</div>

