<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

\yii\helpers\Url::remember();
$static_view=false;

if (!isset($keys)) $keys=null;

$contracts=$model->contracts;
$arms=$model->arms;
$deleteable=!count($arms)&&!count($contracts)&&!count($model->keys);

if (!isset($linksData)) $linksData=null;

$this->title = $model->descr;
$this->params['breadcrumbs']=[];
$breadcrumbs=[];
$breadcrumbs[] = ['label' => \app\models\LicGroups::$titles, 'url' => ['lic-groups/index']];
$breadcrumbs[] = ['label' => $model->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->lic_group_id]];
$breadcrumbs[] = $this->title;

$this->params['headerContent']='<div class="row">'.
	'<div class="col-md-9" >'.
		\yii\bootstrap5\Breadcrumbs::widget(['links' => $breadcrumbs]).
		$this->render('hdr',compact(['model','deleteable'])).
	'</div>'.
	'<div class="col-md-3" >'.
		$this->render('stat',['model'=>$model]).
		$this->render('/attaches/model-list',compact(['model','static_view'])).
	'</div>'.
'</div>';

?>
<div class="lic-items-view">

	<?= $this->render('card',compact(['model','keys','linksData'])) ?>

</div>
