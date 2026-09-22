<?php

use app\components\ShowArchivedWidget;

/* @var $this yii\web\View */
/* @var $models \app\models\Places */

\yii\helpers\Url::remember();
$this->title = \app\models\Places::$title;
$this->params['breadcrumbs'][] = $this->title;

if (!isset($show_archived)) $show_archived=true;

?>
<div class="d-flex flex-wrap align-items-center justify-content-between">
	<div><?= $this->render('hdr_create_obj') ?></div>
	<?php //тогглер без перезагрузки: строки архивных ОС и rowspan ячеек АРМ переключаются на клиенте
	// (ShowArchivedWidget::$scriptOn/$scriptOff, techs/map/arm-row) ?>
	<div class="p-2"><?= ShowArchivedWidget::widget(['reload'=>false,'state'=>(bool)$show_archived]) ?></div>
</div>

<div class="places-index">

	<?php foreach ($models as $model) if (empty($model->parent_id)) {
		echo $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0,'show_archived'=>$show_archived]);
	} ?>
    <br />
</div>
