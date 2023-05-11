<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LoginJournalSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\LoginJournal::$title;
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
		        'value' => function($data) use($renderer){return (is_null($data->comp)||is_null($data->comp->arm))?null:$renderer->render('/techs/item',['model'=>$data->comp->arm]);}
	        ],
	        [
		        'attribute'=>'comps_id',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){return (is_null($data->comp))?null:$renderer->render('/comps/item',['model'=>$data->comp]);}
	        ],
	        'comp_name',
            'time',
			[
				'attribute'=>'type',
				'format'=>'raw',
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
		        'value' => function($data) use($renderer){return (is_null($data->user))?null:$renderer->render('/users/item',['model'=>$data->user]);}
	        ],
        ],
    ]); ?>
</div>
