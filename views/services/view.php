<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Services::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="services-view">

    <?= $this->render('card',['model'=>$model]) ?>
	
</div>

<ul class="nav nav-tabs mb-3 nav-pills" role="tablist">
	<li class="nav-item" role="presentation">
		<a class="nav-link active" id="wiki0-tab" data-bs-toggle="tab" href="#wiki0-tabpanel" role="tab" aria-controls="wiki0-tabpanel" aria-selected="true"> Wiki </a>
	</li>
	<li class="nav-item" role="presentation">
		<a class="nav-link" id="comps-tab" data-bs-toggle="tab" href="#comps-tabpanel" role="tab" aria-controls="comps-tabpanel" aria-selected="false"> Задействованные ОС </a>
	</li>
	<li class="nav-item" role="presentation">
		<a class="nav-link" id="techs-tab" data-bs-toggle="tab" href="#techs-tabpanel" role="tab" aria-controls="techs-tabpanel" aria-selected="false"> Задействованное оборудование </a>
	</li>
</ul>
<div class="tab-content" id="tab-content">
	<div class="tab-pane active wiki-render-area" id="wiki0-tabpanel" role="tabpanel" aria-labelledby="wiki0-tab">
		<?= \app\components\WikiPageWidget::Widget(['list'=>$model->links]) ?>
	</div>
	<div class="tab-pane" id="comps-tabpanel" role="tabpanel" aria-labelledby="comps-tab">
		TODO: разместить список ОС рекурсивно связанных с этим сервисом
		<div id="serviceCompsList"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
		<script>$.get("/web/services/os-list?id=<?= $model->id ?>", function(data) {$("#serviceCompsList").html(data);})</script>
	</div>
	<div class="tab-pane" id="techs-tabpanel" role="tabpanel" aria-labelledby="techs-tab">
		TODO: разместить список ОС рекурсивно связанных с этим сервисом
	</div>
</div>


