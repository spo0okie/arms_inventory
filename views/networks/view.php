<?php

use app\components\UrlParamSwitcherWidget;
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

$showEmpty=\Yii::$app->request->get('showEmpty',false);

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
	<?= UrlParamSwitcherWidget::widget([
		'cssClass'=>'float-end',
		'param'=>'showEmpty',
		'hintOff'=>'Скрыть не занятые IP',
		'hintOn'=>'Показать не занятые IP',
		'label'=>'Пустые',
		'reload'=>false,
		'scriptOn'=>"\$('.empty-item').show();",
		'scriptOff'=>"\$('.empty-item').hide();",
	]) ?>
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
		<?php
			for ($i=0; $i<$model->capacity; $i++) {
				$addr=$model->addr+$i;
				?>
				<tr class="<?= isset($index[$addr])?'':'empty-item' ?>" <?= (isset($index[$addr])||$showEmpty)?'':'style="display:none"' ?>>
					<?= $this->render('ip-row',['model'=>$model,'i'=>$i,'ip'=>isset($index[$addr])?$index[$addr]:null]) ?>
				</tr>
		<?php } ?>
	</table>

</div>
