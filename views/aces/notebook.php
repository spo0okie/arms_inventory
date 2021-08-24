<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */

//if (!isset($static_view)) $static_view=false;

?>

<?php if (strlen($model->notepad)) { ?>
	<h3>Записная книжка:</h3>
	<p>
		<?= Markdown::convert($model->notepad) ?>
	</p>
	<br />
<?php } ?>
