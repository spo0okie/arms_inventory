<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

if (!isset($static_view)) $static_view=false;

?>
    <h1>
		<?= \app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'confirmMessage' => 'Действительно удалить этот расход материала?',
			'name'=>$model->to,
		])?>
    </h1>
	<p><strong>Дата:</strong> <?= $model->date ?></p>
<?php if ($model->cost) { ?>
	<p>
		<strong>Стоимость:</strong> <?= $model->cost.''.$model->currency->symbol. (
		$model->charge?(' (в т.ч. НДС: '.$model->charge.$model->currency->symbol.')'):''
		) ?>
	</p>
<?php } ?>
	<br>
    <br />

    <h4><?= $model->attributeLabels()['materials_id'] ?></h4>
    <p>
	    <?= $this->render('/materials/item',['model'=>$model->material]) ?>
        <br />
        <?= $model->count ?> <?= $model->material->type->units ?> израсходовано
    </p>
    <br />


<?php if (!empty($model->techs_id)) { ?>
    <h4><?= $model->attributeLabels()['techs_id'] ?></h4>
    <p>
		<?= $this->render('/techs/item',['model'=>$model->tech]) ?>
    </p>
    <br />
<?php }
