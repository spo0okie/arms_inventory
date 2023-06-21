<?php
/**
 * Список объектов привязанных к лицензии
 * User: spookie
 * Date: 10.05.2022
 * Time: 13:21
 */

/* @var \yii\data\ArrayDataProvider $dataProvider */

use kartik\grid\GridView;
use kartik\editable\Editable;
use yii\helpers\Html;
if (!isset($static_view)) $static_view=false;
$renderer=$this;

echo GridView::widget([
	'dataProvider' => $dataProvider,
	//'filterModel' => $searchModel,
	'condensed'=>true,
	'columns' => [
		[
			'attribute'=>'objName',
			'label'=>'Закреплено',
			//'modelClass' => \app\models\links\LicLinks::class,
			'format'=>'raw',
			'value'=>function($item) use ($renderer){
				$linkObj=$item->objType;
				//объединили АРМы с оборудованием же
				if ($linkObj=='arms') $linkObj='techs';
				return '<span class="text-nowrap">'.$renderer->render('/'.$linkObj.'/item',[
					'model'=>$item->object,
					'icon'=>true,
					'short'=>true,
				]).'</span>';
			}
		],
		[
			'class'=>'kartik\grid\EditableColumn',
			'label'=>'Комментарий',
			'attribute'=>'comment',
			'editableOptions'=> function ($model, $key, $index) { return [
				'name'=>'comment',
				'header'=>'Комментарий',
				'format'=>Editable::FORMAT_LINK,
				'inputType' => Editable::INPUT_TEXT,
				'inlineSettings' => [
					'templateBefore'=>'<div class="kv-editable-form-inline d-flex w-100 g-0 m-0"><div class="mb-2">{loading}</div>',
				],
				'asPopover' => false,
				'value' => $model['comment'],
				'buttonsTemplate'=>'{submit}',
				'options' => [
					'class' => 'w-100',
					'placeholder'=>'Введите комментарий...',
					'pluginOptions'=>[
						'maxLength'=>255
					],
				],
				'containerOptions'=>['class'=>'row p-0 m-0'],
				'contentOptions'=>['class'=>'p-0 m-0'],
				'inputFieldConfig'=>['options'=>['class'=>'flex-grow-1']],
				'editableValueOptions'=>['class'=>'p-0 text-start kv-editable-link',],
				'formOptions' => [
					'action' => [
						'/lic-links/update-lic-'.$model->licType.'-in-'.$model->objType,
					]
				],
			];},
			/*'contentOptions' => [
			],*/
		],
		[
			'attribute'=>'created_at',
			'label'=>'Когда',
			'format'=>'raw',
			'value'=>function($item) use ($renderer){
				/**
				 * @var $item \app\models\links\LicLinks
				 */
				$value=Yii::$app->formatter->asDate($item->created_at);
				if (is_object($item->creator))
					$value.=' '.$item->creator->getShortName();
				
				$ttip='';
				if ($item->updated_at) {
					$ttip='Обновлено '.\Yii::$app->formatter->asDate($item->updated_at);
					if (is_object($item->updater))
						$ttip.=' ('.$item->updater->getShortName().')';
				}
				if (strlen($ttip))
					$ttip='qtip_ttip="'.$ttip.'"';
					
				return '<span '.$ttip.'>'.$value.'</span>';
			},
		],
		[
			'attribute'=>'unlink',
			'header'=>'<span class="fas fa-trash"/>',
			'format'=>'raw',
			'value'=>function($item) use ($renderer){
				return Html::a('<span class="fas fa-trash"/>',
					[
						'/lic-'.$item->licType.'/unlink',
						'id'=>$item->lic->id,
						$item->objType.'_id'=>$item->object->id
					],
					[
						'data'=>['confirm' => 'Отвязать лицензию от '.$item->objName.'?',],
						'qtip_ttip'=>'Удалить закрепление лицензии'
					]
				);
			},
		],
	
	],
]);