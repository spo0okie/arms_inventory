<?php

use app\components\TextFieldWidget;
use app\models\OrgPhones;

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

$this->title = $model->sname;
$this->params['breadcrumbs'][] = ['label' => OrgPhones::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-phones-view">
	<?= $this->render('card',['model'=>$model]) ?>
	<?php if (strlen($model->service->notebook)) { ?>
		<h4>Записная книжка:</h4>
		<p>
			<?= TextFieldWidget::widget(['model'=>$model->service,'field'=>'notebook']) ?>
		</p>
		<br />
	<?php } ?>
</div>
