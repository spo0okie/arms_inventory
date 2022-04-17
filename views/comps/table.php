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
		case 'name':
			$render_columns[] = [
				'attribute' => 'name',
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/comps/item', ['model' => $data,'icon'=>true]);
				},
				'contentOptions'=>function ($data) use ($column) {return [
					'class'=>'arm_hostname '.$data->updatedRenderClass
				];}
			
			];
			break;

		case 'arm_id':
			$render_columns[] = [
				'attribute' => 'arm_id',
				'label' => 'АРМ',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->arm) ? $renderer->render('/arms/item', ['model' => $data->arm]) : null;
				},
			];
			break;

		case 'ip':
			$render_columns[] = [
				'attribute' => 'ip',
				'format' => 'raw',
				'label' => 'IP Адрес',
				'value' => function ($data) use ($renderer) {
					if (is_object($data)) {
						$output=[];
						foreach ($data->netIps as $ip)
							$output[]=$this->render('/net-ips/item',['model'=>$ip]);
						return implode(' ',$output);
					}
					return null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;

		case 'user_id':
			$render_columns[] = [
				'attribute' => 'user_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->user) ? $renderer->render('/users/item', ['model' => $data->user]) : null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;

		case 'user_position':
			$render_columns[] = [
				'attribute' => 'user_position',
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
		
		case 'place':
			$render_columns[] = [
				'attribute' => 'place',
				'label' => \app\models\Places::$title,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return (is_object($data->arm)&&is_object($data->arm->place)) ?
						$renderer->render('/places/item', ['model' => $data->arm->place, 'full' => 1])
						: null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'departments_id':
			$render_columns[] = [
				'attribute' => 'departments_id',
				'format' => 'raw',
				'value' => function ($data) {
					return is_object($data->department) ? $data->department->name:null;
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'attach':
			$render_columns[] = [
				'attribute' => 'attach',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/arms/item-attachments',['model'=>$data]);
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'state':
			$render_columns[] = [
				'attribute' => 'state',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/tech-states/item', ['model' => $data->state]);
				},
				'contentOptions'=>['class'=>$column.'_col']
			
			];
			break;
		
		case 'comp_hw':
			$render_columns[] = [
				'attribute' => 'comp_hw',
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
		
		case 'os':
			$render_columns[] = [
				'label' => 'Софт',
				'attribute' => $column,
				'contentOptions'=>[
					'class'=>$column.'_col'
				],
			];
			break;
		case 'mac':
			$render_columns[] = [
				'format' => 'ntext',
				'attribute' => $column,
				'contentOptions'=>[
					'class'=>$column.'_col'
				],
			];
			break;
		default:
			$render_columns[] = [
				'attribute' => $column,
				'contentOptions'=>[
					'class'=>$column.'_col'
				],
			];

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

