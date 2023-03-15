<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */

use yii\helpers\Html;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
    $techs = $model->armTechs;
    if (!isset($static_view)) $static_view=false;
    $model_id=$model->id;
?>

<h4>Привязанное оборудование:</h4>
<p>
	<?php if (is_array($techs) && count ($techs)) {
		foreach ($techs as $tech) { ?>
			<?= $this->render('/techs/item',['model'=>$tech,'static_view'=>$static_view]) ?><br />
		<?php } } else { ?>
        отсутствует
	<?php }?>
</p>

