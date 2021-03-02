<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $ips app\models\NetIps[] */
\yii\helpers\Url::remember();

$this->title = $model->sname;
$this->params['breadcrumbs'][] = ['label' => app\models\Networks::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$index=\yii\helpers\ArrayHelper::index($ips,'addr');
//var_dump($index);

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
	<?= ($model->capacity>512)?'<p>Список адресов отображается в режиме <strong>Большой сети</strong>: не созданные IP адреса пропущены, т.к. общее количество адресов более 512':'' ?>
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
		<?php if ($model->capacity>512) {
			foreach ($index as $ip) { ?>
				<tr>
					<?= $this->render('ip-row',['model'=>$model,'i'=>($ip->addr - $model->addr),'ip'=>$ip]) ?>
				</tr>
			<?php }
		} else {
			for ($i=0; $i<$model->capacity; $i++) {
				$addr=$model->addr+$i;
				?>
				<tr>
					<?= $this->render('ip-row',['model'=>$model,'i'=>$i,'ip'=>isset($index[$addr])?$index[$addr]:null]) ?>
				</tr>
		<?php }
		}?>
	</table>

</div>
