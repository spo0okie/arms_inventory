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
			//'header'=>'Закреплено',
			'format'=>'raw',
			'value'=>function($item) use ($renderer){
				return $renderer->render('/'.$item->objType.'/item',[
					'model'=>$item->object,
					'icon'=>true,
				]);
			}
		],
		[
			'class'=>'kartik\grid\EditableColumn',
			'attribute'=>'comment',
			'editableOptions'=> function ($model, $key, $index) { return [
				'name'=>'comment',
				'header'=>'Комментарий',
				'inputType' => Editable::INPUT_TEXT,
				'asPopover' => false,
				'value' => $model['comment'],
				'buttonsTemplate'=>'{submit}',
				'options' => [
					'style' => 'width:300px',
					'placeholder'=>'Введите комментарий...',
					'pluginOptions'=>[
						'maxLength'=>255
					],
				],
				'formOptions' => [
					'action' => [
						'/lic-links/update-lic-'.$model->licType.'-in-'.$model->objType,
					]
				],
			];},
			//'tableOptions' => [
			//],
		],
		[
			'attribute'=>'created_at',
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
					array_merge(
						['/lic-'.$item->licType.'/unlink','id'=>$item->lic->id],
						[$item->objType.'_id'=>$item->object->id]
					),
					[
						'data'=>['confirm' => 'Отвязать лицензию от '.$item->objName.'?',],
						'qtip_ttip'=>'Удалить закрепление лицензии'
					]
				);
			},
		],
	
	],
]);