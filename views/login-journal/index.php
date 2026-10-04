<?php

use app\models\LoginJournal;
use app\models\LoginJournalSearch;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LoginJournalSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = LoginJournal::$title;
//крошки собираются автоматически в layout (views/layouts/main.php)
$renderer=$this;
?>
<div class="login-journal-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= \app\components\DynaGridWidget::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
	        'id',
	        'АРМ'=>[
		        'value' => function($data) use($renderer){return $data?->comp?->arm?->renderItem($renderer);}
	        ],
	        'comps_id'=>[
		        'value' => function($data) use($renderer){return $data?->comp?->renderItem($renderer);}
	        ],
	        'comp_name',
			'calc_time',
			'time',
			'created_at',
			'local_time'=>['value'=> function($data) {
				return $data->local_time ? gmdate('Y-m-d H:i:s', $data->local_time) : null;
			}],
			'type'=>[
				'filter'=>LoginJournal::$types,
				'value' => function($data) use($renderer){
					return LoginJournal::$types[$data->type] ?? 'Unknown';
    			}
			],
			'end_time',
			'end_type'=>[
				//первый вариант фильтра — открытые сессии: у них типа окончания ещё нет
				'filter'=>[LoginJournalSearch::FILTER_OPEN=>'(открыта)']+LoginJournal::$endTypes,
				'value' => function($data) use($renderer){
					/** @var LoginJournal $data */
					if (!$data->isOpen) return $data->endTypeName;
					//для открытой сессии — её состояние: «сейчас» либо «неизвестно» с кнопкой закрытия
					return $renderer->render('/login-journal/status',['model'=>$data]);
				}
			],
			'flags'=>[
				'value' => function($data) {
					/** @var LoginJournal $data */
					return implode(', ',$data->flagsDescr);
				}
			],
            'user_login',
	        'users_id'=>[
		        'value' => function($data) use($renderer){return $data->user?->renderItem($renderer);}
	        ],
        ],
		'defaultOrder'=>['comps_id','comp_name','calc_time','end_time','end_type','user_login','users_id'],
    ]); ?>
</div>
