<?php

use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

if (strlen($model->notepad)) { ?>
	<h3>Записная книжка:</h3>
	<p>
		<?= Markdown::convert($model->notepad) ?>
	</p>
	<br />
<?php }


