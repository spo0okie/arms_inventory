<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.01.2019
 * Time: 3:25
 */



use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

if (!isset($static_view)) $static_view=false;
if (!isset($content_only)) $content_only=false;

if (!$content_only){ ?>
<div
		class="<?= $static_view?'me-1 mb-1 p-1':'me-2 mb-2 p-2'?> <?= $model->archived?'archived-item':''?> org-phones-card"
	<?= ($model->archived&&!(\Yii::$app->request->get('showArchived')))?'style="display:none"':'' ?>>
	<?php } ?>
	
	
	<?php if ($model->archived) echo \app\components\StripedRowWidget::widget(['title'=>'АРХИВИРОВАН']) ?>

	<h3>

	<?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'modal'=>true,
		'confirmMessage'=>'Удалить этот ввод интернет? Это действие необратимо!',
		'static'=>$static_view
	]) ?>
	</h3>

	<p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>
	<p>
		Стоимость: <span class="badge bg-success"><?= Yii::$app->formatter->asCurrency((int)$model->cost) ?></span>
		<?php if ($model->charge) { ?>
			(в т.ч. НДС: <span class="small"><?= Yii::$app->formatter->asCurrency($model->charge) ?></span>)
		<?php } ?>
		/мес
	</p>

<?php if ($model->network) { ?>
	<strong>Подсеть:</strong>
	<?= $this->render('/networks/item',['model'=>$model->network]) ?><br />
<?php } ?>

	<strong>Место подключения:</strong>
	<?= $this->render('/places/item',['model'=>$model->place , 'full'=>true, 'static_view'=>$static_view]) ?>
	<br />

	<strong><?= $model->getAttributeLabel('account')?></strong>
	<?= $model->account ?>

	<?php if ($model->history) { ?>
		<p>
		<strong>Заметки:</strong><br />
		<?= \Yii::$app->formatter->asNtext(trim($model->history)) ?>
		</p>
	<?php } ?>
