<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
\yii\helpers\Url::remember();

$this->title = $model->sname;
$this->params['breadcrumbs'][] = ['label' => app\models\Networks::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="networks-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model]) ?>
		</div>
		<div class="col-md-6">
			<?= $this->render('calc',['model'=>$model]) ?>
		</div>
	</div>
	<br />
	<h4>Адреса:</h4>
	<table class="table table-bordered table-striped table-condensed">
		<tr>
			<th>
				#
			</th>
			<th>
				addr
			</th>
			<th>
				Name
			</th>
			<th>
				comment
			</th>
		</tr>
		<?php for ($i=0; $i<$model->capacity; $i++) { ?>
			<tr>
				<?= $this->render('ip-row',['model'=>$model,'i'=>$i]) ?>
			</tr>
		<?php } ?>
	</table>

</div>
