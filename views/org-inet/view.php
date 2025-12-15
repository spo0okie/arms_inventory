<?php

use app\components\TextFieldWidget;
use app\models\OrgInet;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */
Url::remember();

$static_view=false;
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => OrgInet::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-inet-view">
	<?= $this->render('/services/card',['model'=>$model->service ,'static_view'=>$static_view]) ?>
	<?php if (strlen($model->service->notebook)) { ?>
		<h4>Записная книжка:</h4>
		<p>
			<?= TextFieldWidget::widget(['model'=>$model->service,'field'=>'notebook']) ?>
		</p>
		<br />
	<?php } ?>
</div>
