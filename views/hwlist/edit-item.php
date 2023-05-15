<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 07.03.2018
 * Time: 23:02
 * @var \app\models\HwListItem $item отображаемый элемент
 * @var \app\models\OldArms    $model объект компьютера или АРМа, из которого вызвано
 * @var array                  $manufacturers список производителей
 * @var bool                   $addItem признак того, что это не настоящий элемент а пустышка для добавления элемента в паспорт
 */
use yii\bootstrap5\Modal;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

if (!isset($static_view)) $static_view=false;
?>

<div class="edit-hw-item">
	<?php $form = ActiveForm::begin([
		//'action' => ['techs/updhw','id'=>$model->id,'uid'=>$item->uid],
		//'method' => 'get',
		'options'=>[
			'onsubmit'=>'window.location.replace("'.\yii\helpers\Url::to([
				'techs/updhw',
				'id'=>$model->id,
				'uid'=>$item->uid
			]).'"'.
				'+"&title="+$("[name=\'title\']").val()'.
				'+"&manufacturer_id="+$("[name=\'manufacturer_id\']").val()'.
				'+"&manual_name="+$("[name=\'manual_name\']").val()'.
				'+"&manual_sn="+$("[name=\'manual_sn\']").val()'.
				'+"&inv_num="+$("[name=\'inv_num\']").val()'.
				
				'); return false;',
		],
	]); ?>
		<table>
			<thead>
			<td>Оборудование<br />
				<div class="hint-block">Системный тип:<br /><?= $item->type ?></div>
			</td>
			<td>Производитель<br />
				<div class="hint-block">Ориг.:<br /><?= $item->manufacturer ?></div>
			</td>
			<td>Наименование<br />
				<div class="hint-block">Перекроет исходное:<br /><?= $item->product ?></div>
			</td>
			<td>Серийный №<br />
				<div class="hint-block">Перекроет исходный:<br /><?= $item->sn ?></div>
			</td>
			<td>
				Инвентарный №<br />
				<div class="hint-block">Вводится только вручную</div>
			</td>
			</thead>
			<tr>
				<td><?= \yii\helpers\BaseHtml::input('string','title',$item->title,['class'=>'form-control']) ?></td>
				<td><?= \yii\helpers\BaseHtml::dropDownList('manufacturer_id',$item->manufacturer_id,\app\models\Manufacturers::fetchNames() ,['class'=>'form-control']) ?></td>
				<td><?= \yii\helpers\BaseHtml::input('string','manual_name',$item->manual_name,['class'=>'form-control']) ?></td>
				<td><?= \yii\helpers\BaseHtml::input('string','manual_sn',$item->manual_sn,['class'=>'form-control']) ?></td>
				<td><?= \yii\helpers\BaseHtml::input('string','inv_num',$item->inv_num,['class'=>'form-control']) ?></td>
			</tr>
		</table>


		<div class="form-group">
			<p><?= \yii\helpers\BaseHtml::checkbox('hidden',$item->hidden,['label'=>'Скрыть элемент из паспорта']) ?></p>
			<?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
		</div>

	<?php ActiveForm::end(); ?>

</div>
