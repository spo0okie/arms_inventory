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

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
	        'id',
	        [
		        'attribute'=>'АРМ',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){return $data?->comp?->arm?->renderItem($renderer);}
	        ],
	        [
		        'attribute'=>'comps_id',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){return $data?->comp?->renderItem($renderer);}
	        ],
	        'comp_name',
            'calc_time:datetime',
			[
				'attribute'=>'type',
				'format'=>'raw',
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
	        [
		        'attribute'=>'users_id',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){return $data->user?->renderItem($renderer);}
	        ],
        ],
    ]); ?>
</div>
