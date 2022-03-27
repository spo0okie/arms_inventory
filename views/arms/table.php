<?php

use kartik\grid\GridView;

/**
 * Это рендер списка АРМов, вынесен отдельным файлом, т.к. нужен много где:
 * в списке АРМов
 *
 */


/* @var $this yii\web\View */
/* @var $searchModel app\models\ArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer = $this;
if (!isset($columns)) $columns=['attach','num','model','comp_id','comp_ip','sn','state','user_id','places_id'];

$manufacturers=\app\models\Manufacturers::fetchNames();

//формируем список столбцов для рендера
$render_columns=[];
foreach ($columns as $column) {

	switch ($column) {
		case 'num':
			$render_columns[] = [
				'attribute' => $column,
				'label' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/arms/item', ['model' => $data]);
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;

		case 'model':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->techModel) ? $renderer->render('/tech-models/item', ['model' => $data->techModel, 'static' => true]) : null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;

		case 'comp_id':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->comp) ? $renderer->render('/comps/item', ['model' => $data->comp]) : null;
				},
				'contentOptions'=>function ($data) use ($column) {return [
					'class'=>'arm_hostname '.$data->updatedRenderClass
				];}
			
			];
			break;
		
		case 'comp_ip':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'label' => 'IP Адрес',
				'value' => function ($data) use ($renderer) {
					if (is_object($data->comp)) {
						$output=[];
						foreach ($data->comp->netIps as $ip)
							$output[]=$this->render('/net-ips/item',['model'=>$ip]);
						return implode(' ',$output);
					}
					return null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'comp_mac':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'ntext',
				'label' => 'MAC адрес',
				'value' => function ($data) use ($renderer) {
					if (is_object($data->comp)) {
						return $data->comp->formattedMac;
					}
					return null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'user_id':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->user) ? $renderer->render('/users/item', ['model' => $data->user]) : null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;

		case 'user_position':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'label' => 'Должность',
				'value' => function ($data) use ($renderer) {
					return is_object($data->user) ?
						"<span class='arm_user_position'>{$data->user->Doljnost}</span>"
						: null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'places_id':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->place) ? $renderer->render('/places/item', ['model' => $data->place, 'full' => 1]) : null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'departments_id':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) {
					return (is_object($data->user) && is_object($data->user->orgStruct)) ? $data->user->orgStruct->name:null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'attach':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/arms/item-attachments',['model'=>$data]);
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'state':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/tech-states/item', ['model' => $data->state]);
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'comp_hw':
			$render_columns[] = [
				'attribute' => $column,
				'label' => 'HW',
				'format' => 'raw',
				'value' => function ($data) use ($manufacturers) {
					if (is_object($data->comp)) {
						$render=[];
						foreach ($data->comp->getHardArray() as $item)
							$render[]=$item->getName().' '.$item->getSN();
						return implode(' ',$render);
					}
					return null;
				},
				'contentOptions'=>function ($data) use ($column) {
					$render=[];
					if (is_object($data->comp)) {
						foreach ($data->comp->getHardArray() as $item)
							$render[] = $item->getName() . ' ' . $item->getSN();
					}
					return [
						'class'=>'comp_hw_col',
						'qtip_ttip' => implode('<br />',$render)
					];
				}
			];
			break;

		case 'sn':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'label' => 'SN',
				'contentOptions'=>function ($data) use ($column) {
					$opts=['class'=>$column.'_col'];
					if (isset($data->$column) && strlen($data->$column)) $opts['qtip_ttip']=$data->$column;
					return $opts;
				},
			];
			break;
		case 'inv_num':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'label' => 'Бух. инв.',
				'contentOptions'=>function ($data) use ($column) {
					$opts=['class'=>$column.'_col'];
					if (isset($data->$column) && strlen($data->$column)) $opts['qtip_ttip']=$data->$column;
					return $opts;
				},
			];
			break;


	}
}

//try {
	echo GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => $render_columns,
		'tableOptions' => ['class'=>'table-condensed table-striped table-bordered arms_index'],
		'resizableColumns'=>false,
	]);
//} catch (Exception $e) {
//	echo 'Ошибка вывода виджета таблицы<br/>';
// }

