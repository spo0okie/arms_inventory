<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */

use yii\helpers\Html;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
    $licGroups=$model->licGroups;
    $licItems=$model->licItems;
    $licKeys=$model->licKeys;
    if (!isset($static_view)) $static_view=false;
    $model_id=$model->id;
?>

<h4>Назначенные лицензии:</h4>
<p id="arms_<?= $model->id ?>_attached_lics">
	
	<?php if (count($licKeys) || count($licItems) || count ($licGroups)) {
		foreach ($licKeys as $key)
			echo $key->renderItem($this,['static_view'=>$static_view,'name'=>$key->sname]).'<br />';
		
		foreach ($licItems as $item)
			echo $item->renderItem($this,['static_view'=>$static_view,'name'=>$item->sname]).'<br />';

		foreach ($licGroups as $group)
			echo  $group->renderItem($this,['static_view'=>$static_view]).'<br />';

	} else { ?>
        отсутствуют
	<?php }?>

</p>

