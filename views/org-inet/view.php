<?php

use app\components\TextFieldWidget;
use app\models\OrgInet;
use yii\helpers\Url;


use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */
Url::remember();

$static_view=false;
$this->title = $model->name;
//крошки собираются автоматически в layout (views/layouts/main.php)
?>
<div class="org-inet-view">
	<?= ModelWidget::widget(['model'=>$model->service,'view'=>'card','options'=>['static_view'=>$static_view]]) ?>
	<?php if (strlen($model->service->notebook??'')) { ?>
		<h4>Записная книжка:</h4>
		<p>
			<?= \app\components\ModelFieldWidget::renderFieldValue($model->service,'notebook') ?>
		</p>
		<br />
	<?php } ?>
</div>


