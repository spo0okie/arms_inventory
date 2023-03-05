<?php

/* Колонки для Материалы по кучкам Помещение/Тип
 */
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $groupBy string */

$renderer=$this;

return  [
	[
		'attribute'=>'place',
		'format'=>'raw',
		'value' => function($data) use($renderer){
			return $renderer->render('/places/item',['model'=>$data['models'][0]->place,'full'=>true]);
		}
	],
	[
		'attribute'=>'model',
		'format'=>'raw',
		'value' => function($data) use($renderer,$groupBy){
			if (count($data['models'])==1) {
				//если у нас 1 материал то ссылка будет прямо на него
				return $renderer->render('/materials/item',[
					'model'=>$data['models'][0],
					'name'=>$data['model']]
				);
			} else {
				if ($groupBy=='type')
					$link=['materials/index','MaterialsSearch[type_id]'=>$data['type_id'],'MaterialsSearch[places_id]'=>$data['place_id']];
				if ($groupBy=='name')
					$link=['materials/index','MaterialsSearch[model]'=>$data['name'],'MaterialsSearch[places_id]'=>$data['place_id']];
				return '<span class="material-item cursor-default" qtip_ajxhrf="' .
					\yii\helpers\Url::to([
						'/materials/ttips',
						'ids' => implode(',', \app\helpers\ArrayHelper::getColumn($data['models'], 'id')),
						'hide_places' => 1,
						'hide_usages' => 1
					]) . '">' .
					Html::a($data['model'], $link) .
					'</span>';
			}
		}
	],
	[
		'attribute'=>'rest',
		'format'=>'raw',
		'value' => function($data) use($renderer,$groupBy){
			$rest=0;
			$models=$data['models'];
			/**
			 * @var $models \app\models\Materials[]
			 */
			\app\helpers\ArrayHelper::multisort($models,'rest');

			foreach ($models as $model)
				$rest+=$model->rest;
			
			$model=$models[0];

			$link=[];
			if (count($models)==1) {
				//если у нас 1 материал то ссылка будет прямо на него
				$link=['materials/view','id'=>$model];
			} else {
				//иначе на поиск
				if ($groupBy=='type')
					$link=['materials/index','MaterialsSearch[type_id]'=>$data['type_id'],'MaterialsSearch[places_id]'=>$data['place_id']];
				if ($groupBy=='name')
					$link=['materials/index','MaterialsSearch[model]'=>$data['name'],'MaterialsSearch[places_id]'=>$data['place_id']];
			}
			return '<span class="material-item cursor-default" qtip_ajxhrf="'.
				\yii\helpers\Url::to([
					'/materials/ttips',
					'ids'=>implode(',',\app\helpers\ArrayHelper::getColumn($models,'id')),
					'hide_places'=>1,
					'hide_usages'=>1
				]).'">'.
				Html::a($rest.' '. $model->type->units,$link).
				'</span>';
		}
	],
];