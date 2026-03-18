<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */

use yii\helpers\Html;
use yii\bootstrap5\Modal;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Techs */
    $techs = $model->installedTechs;
    if (!isset($static_view)) $static_view=false;
    $model_id=$model->id;
?>

<h4>Установленное оборудование:</h4>
<p>
	<?php if (is_array($techs) && count ($techs)) {
		foreach ($techs as $tech) { ?>
			<?= ModelWidget::widget(['model'=>$tech,'options'=>['static_view'=>$static_view]]) ?><br />
		<?php } } else { ?>
        отсутствует
	<?php }?>
</p>



