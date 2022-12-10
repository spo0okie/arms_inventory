<?php

use yii\helpers\Html;
use yii\grid\GridView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
return [
	'objects'=>[
		'value'=>function($data) use ($renderer) {
			$items=[];
			if (count($data->acls)) foreach ($data->acls as $acl) {
				foreach ($acl->aces as $ace) {
					foreach ($ace->users as $user)
						$items[$user->shortName]=$this->render('/users/item',['model'=>$user,'static_view'=>true,'icon'=>true,'short'=>true]);
					
					foreach ($ace->comps as $comp)
						$items[$comp->name]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true,'icon'=>true]);
					
					foreach ($ace->netIps as $ip)
						$items[$ip->sname]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'icon'=>true,'no_class'=>true]);
					
					if (strlen($ace->comment))
						$items[$ace->comment]=$ace->comment;
					
					ksort($items,SORT_STRING);
				}
			}
			ksort($items,SORT_STRING);
			return \app\components\ExpandableCardWidget::widget(['content'=>implode('<br />',$items)]);
		}
	],
	'resources'=>[
		'value'=>function($data) use ($renderer) {
			$output=[];
			if (count($data->acls)) foreach ($data->acls as $acl) {
				$output[$acl->sname]=$renderer->render('/acls/resource',['model'=>$acl,'static_view'=>true]);
			}
			return \app\components\ExpandableCardWidget::widget(['content'=>implode('<br />',$output)]);
		}
	],
	'name'=>[
		'value'=>function($data) use ($renderer) {
			$output=[Html::a($data->name,['view','id'=>$data->id])];
			if ($data->description) $output[]=$data->description;
			//if ($data->history) $output[]=Markdown::convert($data->history);
			return implode('<br />',$output);
		}
	],
	'accessPeriods'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data \app\models\Schedules
			 */
			$output=[];
			if (is_array($periods=$data->findPeriods(null,null)) && count($periods))
				foreach ($periods as $period) {
					$output[]=Html::tag(
						'span',
						str_replace(' ','&nbsp;',$period->periodSchedule),
						[
							'title'=>Yii::$app->formatter->asNtext($period->comment),
							'class'=>"text-nowrap p-1 ".($period->is_work?"bg-success":"bg-danger")
						]
					);
				}
			return implode('<br />',$output);
		},
		'contentOptions'=>function ($data) {
			/**
			 * @var $data \app\models\Schedules
			 */
			$working=$data->isWorkTime( date('Y-m-d'),date('H:i:s'));
			return [
				'class'=>$working?'bg-green-striped border-2':'bg-red-striped border-2',
			];
		}
	],
	'acePartners'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data \app\models\Schedules
			 */
			$items=[];
			if (count($data->acePartners)) foreach ($data->acePartners as $partner) {
				$items[]=$this->render('/partners/item',['model'=>$partner,'static_view'=>true]);
			}
			ksort($items,SORT_STRING);
			return implode('<br />',$items);
		}
	],
	'aclSegments'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data \app\models\Schedules
			 */
			$items=[];
			if (count($data->aclSegments)) foreach ($data->aclSegments as $segment) {
				$items[]=$this->render('/segments/item',['model'=>$segment,'static_view'=>true]);
			}
			ksort($items,SORT_STRING);
			return implode('<br />',$items);
		}
	],
	'aceDepartments'=>[
		'value'=>function($data) {
			/**
			 * @var $data \app\models\Schedules
			 */
			$items=[];
			if (count($data->aceDepartments)) foreach ($data->aceDepartments as $department) {
				$items[]=$this->render('/org-struct/item',['model'=>$department,'static_view'=>true]);
			}
			ksort($items,SORT_STRING);
			return implode('<br />',$items);
		}
	],
	'aclSites'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data \app\models\Schedules
			 */
			$items=[];
			if (count($data->aclSites)) foreach ($data->aclSites as $site) {
				$items[]=$this->render('/places/item',['model'=>$site,'static_view'=>true,'short'=>true]);
			}
			ksort($items,SORT_STRING);
			return implode('<br />',$items);
		}
	],
	'accessTypes'=>[
		'value'=>function($data) use ($renderer) {
			/**
			 * @var $data \app\models\Schedules
			 */
			$items=[];
			if (count($data->accessTypes)) foreach ($data->accessTypes as $type) {
				$items[]=$this->render('/access-types/item',['model'=>$type,'static_view'=>true]);
			}
			ksort($items,SORT_STRING);
			return implode('<br />',$items);
		}
	],
];
