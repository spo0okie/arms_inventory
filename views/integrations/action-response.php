<?php

use app\components\integrations\ActionResult;
use app\components\integrations\IntegrationProvider;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $provider IntegrationProvider */
/* @var $descriptor array дескриптор действия */
/* @var $result ActionResult */

if (!isset($modalParent)) $modalParent = null;

$title = $descriptor['title'] ?? $provider->getTitle();

?>

<h1><?= Html::encode($title) ?></h1>

<?php if ($result->html !== '') {
	//расширенный вывод провайдера (HTML на его совести)
	echo $result->html;
} else { ?>
	<div class="alert <?= $result->ok ? 'alert-success' : 'alert-danger' ?>">
		<?= Html::encode($result->message !== '' ? $result->message
			: ($result->ok ? 'Выполнено' : 'Не выполнено')) ?>
	</div>
<?php } ?>
