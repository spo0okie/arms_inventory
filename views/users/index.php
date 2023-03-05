<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Users::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="users-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать нового', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            'employee_id',

            [
                'attribute'=>'Ename',
                'format'=>'raw',
                'value' => function($data) use($renderer){return $renderer->render('/users/item',['model'=>$data]);}
            ],
            'Doljnost',
			[
				'attribute'=>'orgStruct_name',
				'format'=>'raw',
				'header'=>$searchModel->getAttributeLabel('Orgeh'),
				'value' => function($data) use($renderer){return $renderer->render('/org-struct/item',['model'=>$data->orgStruct]);}
			],
	        
            //'Persg',
            //'Uvolen',
            'Login',
            'Email:email',
            'Phone',
	        [
		        'attribute'=>'techs',
		        'format'=>'raw',
		        'value' => function($data)use($renderer){
                    $arms=$data->techs;
                    if (is_array($arms)) {
                        if (count($arms)==0) {
	                        return 'Не назначено';
                        } else {
                        	$items=[];
	                        foreach ($arms as $arm)
	                        	$items[]=$renderer->render('/techs/item',['model'=>$arm,'static_view'=>true]);
	                        return implode('<br />',$items);
                        }
                    }
                }
	        ],
            'Mobile',
            //'work_phone',
            //'Bday',
            //'manager_id',
            //'nosync',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
