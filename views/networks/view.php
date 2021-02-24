<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

$this->title = $model->sname;
$this->params['breadcrumbs'][] = ['label' => app\models\Networks::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="networks-view">
	<?= $this->render('card',['model'=>$model]) ?>
	<br />
	<h4>Адреса:</h4>
	<table class="table table-bordered table-striped">
		<tr>
			<th>
				#
			</th>
			<th>
				addr
			</th>
			<th>
				obj
			</th>
			<th>
				comment
			</th>
		</tr>
		<?php for ($i=1; $i<$model->capacity; $i++) { ?>
			<tr>
				<?= $this->render('ip-row',['model'=>$model,'i'=>$i]) ?>
			</tr>
		<?php } ?>
	</table>

</div>
