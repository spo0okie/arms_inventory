<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
return [
	'employee_id',
	'Ename'=>[	'value' => function($data) use($renderer) {return $renderer->render('/users/item',['model'=>$data]);}	],
	'shortName'=>[	'value' => function($data) use($renderer) {return $renderer->render('/users/item',['model'=>$data,'short'=>true]);}],
	'Doljnost',
	'org_name'=>[
		'value' => function($data) use($renderer){
			if (!is_object($data->org)) return null;
			return $renderer->render('/partners/item',['model'=>$data->org,'static_view'=>true]);
		}
	],
	'orgStruct_name'=>[
		'value' => function($data) use($renderer){return $renderer->render('/org-struct/item',['model'=>$data->orgStruct]);}
	],
	'Login',
	'Email'=>['format'=>'email'],
	'Phone'=>[
		'value' => function($data) use($renderer){
			$techs=$data->techs;
			if (!is_array($techs) || count($techs)==0) {
				return $data->Phone;
			} else {
				$items=[];
				foreach ($techs as $tech)
					if ($tech->isVoipPhone && strlen($tech->comment))
						$items[]=$renderer->render('/techs/item',['model'=>$tech,'static_view'=>true,'name'=>$tech->comment]);
				return count($items)?implode(' ',$items):$data->Phone;
			}
		}
	],
	'techs'=>[
		'value' => function($data)use($renderer){
			$arms=$data->techs;
			if (!is_array($arms)||! count($arms)) {
					return null;
			} else {
				$items=[];
				foreach ($arms as $arm)
					$items[]=$renderer->render('/techs/item',['model'=>$arm,'static_view'=>true]);
				return implode('<br />',$items);
			}
		}
	],
	'arms'=>[
		'value' => function($data)use($renderer){
			$arms=$data->techs;
			if (!is_array($arms)||! count($arms)) {
				return null;
			} else {
				$items=[];
				foreach ($arms as $arm)
					if ($arm->model->type->is_computer??false)
						$items[]=$renderer->render('/techs/item',['model'=>$arm,'static_view'=>true]);
				return implode('<br />',$items);
			}
		}
	],
	'Mobile'=>[
		'value' => function($data) {
			$tokens = [];
			if ($data->Mobile) $tokens[] = $data->Mobile;
			if ($data->private_phone) $tokens[] = $data->private_phone;
			return implode(", ",$tokens);
		}
	],
];
