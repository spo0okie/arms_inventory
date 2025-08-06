<?php

use app\models\LoginJournal;
use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LoginJournalSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = LoginJournal::$title;
$this->params['breadcrumbs'][] = $this->title;
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
			'calc_time'=>['format'=>'datetime'],
			'time'=>['format'=>'datetime'],
			'local_time'=>['format'=>'datetime','value'=> function($data) {
				return $data->local_time ? gmdate('Y-m-d H:i:s', $data->local_time) : null;
			}],
			'type'=>[
				'filter'=>[
					0 => 'CON',
					1 => 'RDP',
				],
				'value' => function($data) use($renderer){
    				switch ($data->type) {
						case 0: return 'CON';
						case 1: return 'RDP';
						default: return 'Unknown';
					}
    			}
			],
            'user_login',
	        'users_id'=>[
		        'value' => function($data) use($renderer){return $data->user?->renderItem($renderer);}
	        ],
        ],
		'defaultOrder'=>['comps_id','comp_name','calc_time','user_login','users_id'],
    ]); ?>
</div>
