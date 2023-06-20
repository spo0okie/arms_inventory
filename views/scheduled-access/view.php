<?php

use yii\helpers\Html;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

$acl_mode=(count($model->acls));
if (!isset($static_view)) $static_view=false;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Acls::$scheduleTitles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\helpers\Url::remember();

$providingServices=$model->providingServices;
$supportServices=$model->supportServices;
$acls=$model->acls;

$deleteable=!count($providingServices) && !count($supportServices) ;

$schedule_id=$model->id;
\yii\web\YiiAsset::register($this);
?>
<div class="schedules-view">
	<h1>
		<?= $static_view?Html::a($model->name,['scheduled-access/view','id'=>$model->id]):$model->name ?>
		
		<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt" title="Изменить"></span>',['scheduled-access/update','id'=>$model->id])) ?>
		
		<?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash" title="Удалить"/>', ['scheduled-access/delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить это расписание? Это действие необратимо!',
				'method' => 'post',
			],
		]); else { ?>
			<span class="small">
			<span class="fas fa-lock" title="Невозможно в данный момент удалить это расписание, удалить можно только пустое расписание не привязанное ни к каким объектам."></span>
		</span>
		<?php } ?>&nbsp;
	</h1>
	<p><?= $model->description ?></p>

		<div class="row">
			<div class="col-md-6">
				<?= $this->render('acl',['model'=>$model]) ?>
			</div>
			<div class="col-md-6">
				<?= $this->render('exceptions',['model'=>$model]) ?>
				<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
				<?php if (strlen($model->history)) { ?>
					<br /><br />
					<h3>Записная книжка:</h3>
					<p>
						<?= Markdown::convert($model->history) ?>
					</p>
				<?php } ?>
			</div>
		</div>
</div>
