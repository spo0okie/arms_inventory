<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.01.2019
 * Time: 3:25
 */


use app\components\LinkObjectWidget;
use app\components\StripedRowWidget;
use app\components\TextFieldWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */

if (!isset($static_view)) $static_view=false;
if (!isset($content_only)) $content_only=false;

$currency='';
if (is_object($model->service) && is_object($model->service->currency)) {
	$currency=' '.$model->service->currency->symbol;
}

if (!$content_only){ ?>
	<div
		class="<?= $static_view?'me-1 mb-1 p-1':'me-2 mb-2 p-2'?> <?= $model->archived?'archived-item':''?> org-phones-card"
		<?= ($model->archived&&!(Yii::$app->request->get('showArchived')))?'style="display:none"':'' ?>>
<?php } ?>

	
		<?php if ($model->archived) echo StripedRowWidget::widget(['title'=>'АРХИВИРОВАН']) ?>
	
		<div class="float-end">
			<h3>
				<?= LinkObjectWidget::widget([
					'model'=>$model,
					'name'=>'',
					'static'=>$static_view,
					'modal'=>true,
				]) ?>
				
			</h3>
		</div>
		<h3><?= $model->title ?></h3>
		
		<p>	<?= TextFieldWidget::widget(['model'=>$model,'field'=>'untitledComment']) ?> </p>
		<p>
			Стоимость: <span class="badge bg-success"><?= number_format((int)$model->cost,0,'',' ').$currency ?></span>
			<?php if ($model->charge) { ?>
				(в т.ч. НДС: <span class="small"><?= number_format($model->charge,0,'',' ').$currency ?></span>)
			<?php } ?>
			/мес
		</p>
		
		<strong>Место подключения:</strong>
		<?= $this->render('/places/item',['model'=>$model->place , 'full'=>true, 'static_view'=>$static_view]) ?>
		<br />
		<strong><?= $model->getAttributeLabel('account')?></strong>
		<?= $model->account ?>
<?php if (!$content_only){ ?>
	</div>
<?php } ?>
