<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\TextFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

if (!isset($static_view)) $static_view=false;

?>

<div class="d-flex flex-wrap flex-row-reverse">
	<div class="small opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></div>
	<div class="flex-fill"><h1>
			<?= LinkObjectWidget::widget([
				'model'=>$model,
				'confirmMessage' => 'Действительно удалить этот расход материала?',
				'name'=>$model->to,
			])?>
		</h1>
	</div>
</div>

	<p class="mb-3">
		<strong>Дата:</strong> <?= $model->date ?><br />
<?php if ($model->cost) { ?>
		<strong>Стоимость:</strong> <?= $this->render('/contracts/price',[
			'total'=>$model->cost,
			'charge'=>$model->charge,
			'currency'=>$model->currency->symbol,
		]) ?>
<?php } ?>
	</p>

    <h4><?= $model->attributeLabels()['materials_id'] ?></h4>
    <p class="mb-3">
	    <?= $this->render('/materials/item',['model'=>$model->material]) ?>
        <br />
        <?= abs($model->count) ?> <?= $model->material->type->units ?> <?= $model->count<0?'получено':'израсходовано' ?>
    </p>


<?php if (!empty($model->techs_id)) { ?>
    <h4><?= $model->attributeLabels()['techs_id'] ?></h4>
    <p class="mb-3">
		<?= $this->render('/techs/item',['model'=>$model->tech]) ?>
    </p>
<?php }


echo TextFieldWidget::widget(['model'=>$model,'field'=>'history']) ?>
