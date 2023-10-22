<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

if (!isset($static_view)) $static_view=false;

?>
    <h1>
		<?= LinkObjectWidget::widget([
			'model'=>$model,
			'confirmMessage' => 'Действительно удалить этот расход материала?',
			'name'=>$model->to,
		])?>
    </h1>
	<p><strong>Дата:</strong> <?= $model->date ?></p>
<?php if ($model->cost) { ?>
	<p>
		<strong>Стоимость:</strong> <?= $this->render('/contracts/price',[
			'total'=>$model->cost,
			'charge'=>$model->charge,
			'currency'=>$model->currency->symbol,
		]) ?>
	</p>
<?php } ?>
	<br>
    <br />

    <h4><?= $model->attributeLabels()['materials_id'] ?></h4>
    <p>
	    <?= $this->render('/materials/item',['model'=>$model->material]) ?>
        <br />
        <?= abs($model->count) ?> <?= $model->material->type->units ?> <?= $model->count<0?'получено':'израсходовано' ?>
    </p>
    <br />


<?php if (!empty($model->techs_id)) { ?>
    <h4><?= $model->attributeLabels()['techs_id'] ?></h4>
    <p>
		<?= $this->render('/techs/item',['model'=>$model->tech]) ?>
    </p>
    <br />
<?php }
