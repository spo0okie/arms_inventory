<?php


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

\yii\helpers\Url::remember();

$this->title = $model->descr;
$static_view=false;
$breadcrumbs=[];
$breadcrumbs[] = ['label' => \app\models\LicGroups::$titles, 'url' => ['index']];
$breadcrumbs[] = $this->title;

$this->params['headerContent']='<div class="row">'.
	'<div class="col-md-9" >'.
	\yii\bootstrap5\Breadcrumbs::widget(['links' => $breadcrumbs]).
	$this->render('hdr',compact(['model'])).
	'</div>'.
	'<div class="col-md-3" >'.
	$this->render('usage',['model'=>$model]).
	$this->render('/attaches/model-list',compact(['model','static_view'])).
	'</div>'.
	'</div>';

?>
<div class="lic-groups-view">
    <?= $this->render('card',compact(['model','dataProvider','searchModel','linksData'])) ?>
</div>
