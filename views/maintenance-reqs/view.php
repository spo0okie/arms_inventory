<?php

use app\components\WikiPageWidget;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */

//\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\MaintenanceReqs::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

$wikiLinks= WikiPageWidget::getLinks($model->links);

?>
<div class="maintenance-reqs-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>
<?php
if (count($wikiLinks)) foreach ($wikiLinks as $name=>$url) {
	echo WikiPageWidget::Widget(['list'=>$model->links,'item'=>$name]);
	break;
}
