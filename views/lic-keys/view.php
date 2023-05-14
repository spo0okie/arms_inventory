<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */

\yii\helpers\Url::remember();

$this->title = $model->keyShort;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$title, 'url' => ['lic-groups/index']];
$this->params['breadcrumbs'][] = ['label' => $model->licItem->licGroup->descr, 'url' => ['lic-groups/view','id'=>$model->licItem->lic_group_id]];
$this->params['breadcrumbs'][] = ['label' => $model->licItem->descr, 'url' => ['lic-items/view','id'=>$model->lic_items_id]];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>
<div class="lic-keys-view">
	<div class="row">
		<div class="col-md-4">

		    <?= $this->render('card',compact('model')) ?>
		</div>
		<div class="col-md-8">
			<h4>Привязки</h4>
			<?= $this->render('/lic-links/obj-list', ['dataProvider' => $linksData]); ?>
		</div>

</div>
