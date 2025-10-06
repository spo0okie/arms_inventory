<?php

use app\components\LinkObjectWidget;
use app\components\UrlListWidget;
use kartik\markdown\Markdown;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

if (!isset($static_view)) $static_view=false;
?>

<div class="soft-ttip ttip-card">
	<h1>
		<?= LinkObjectWidget::widget([
			'model'=>$model->manufacturer,
			'static'=>true,
		]) ?>
		<?= LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$model->descr,
			'static'=>true,
		]) ?>
	</h1>
	
	<?= Yii::$app->formatter->asNtext($model->comment) ?>
	
	<?php if (is_array($model->softLists)&&count($model->softLists)) { ?>
		В списках:
		<ul>
			<?php foreach ($model->softLists as $item) { ?>
				<li><?= Html::a($item->descr,['soft-lists/view','id'=>$item->id]) ?></li>
			<?php } ?>
		</ul>
	<?php } else { ?>
		В списки не включено
	<?php } ?>
	
	<?php if ($static_view && is_array($model->scans)&&count($model->scans)) echo $this->render('/scans/thumb',[
		'model'=>$model->scans[0],
		'soft_id'=>$model->id,
		'static_view'=>true
	]); ?>
	
	<?php if ($model->links) { ?>
		<h4>Ссылки:</h4>
		<p class="mb-4">
			<?= UrlListWidget::Widget(['list'=>$model->links]) ?>
		</p>
	<?php } ?>
	
	<?php if (isset($hitlist) && ($hitlist!=='null')) { ?>
		<h4>Список regexp совпадений:</h4>
		<p class="mb-4">
			<?= Yii::$app->formatter->asNtext($hitlist) ?>
		</p>
	<?php } else {
		echo "Обнаружений: ".(count($model->hits)?('<span class="badge bg-primary">'.count($model->hits).'</span>'): 'нет');
		echo "<br />";
		echo "Внесений в паспорт: ".(count($model->comps)?('<span class="badge bg-primary">'.count($model->comps).'</span>'): 'нет');
	}?>
</div>