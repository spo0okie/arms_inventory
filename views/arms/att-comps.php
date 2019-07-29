<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */

use yii\helpers\Html;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */
    $comps = $model->comps;
    if (!isset($static_view)) $static_view=false;
    $model_id=$model->id;
?>

<h4>Привязанные ОС:</h4>
<p>
	<?php if (is_array($comps) && count ($comps)) {
		foreach ($comps as $comp) { ?>
			<?= $this->render('/comps/item',['model'=>$comp,'static_view'=>$static_view]) ?><br/>
		<?php } } else { ?>
        отсутствуют
	<?php }?>
</p>

