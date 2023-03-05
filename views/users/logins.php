<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;
$dataProvider->query->andWhere(['Uvolen'=>0])
    ->andWhere(['not', 'Login = ""']);
$renderer=$this;
?>
<div class="users-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
	        'Login',

            [
                'attribute'=>'Ename',
                'format'=>'raw',
                'value' => function($data){return Html::a($data['Ename'],['view', 'id' => $data['id']]);}
            ],
            'Doljnost',
	        //'orgStruct.name',
            //'Persg',
            //'Uvolen',
            //'Email:email',
            'Phone:raw:Тел.',
	        [
		        'attribute'=>'Arms',
		        'format'=>'raw',
		        'value' => function($data) use ($renderer){
                    $arms=$data->arms;
                    if (is_array($arms)) {
                        if (count($arms)) {
	                        $items=[];
	                        foreach ($arms as $arm) {
		                        $items[]=$renderer->render('/techs/item',['model'=>$arm]);
	                        }
	                        return implode('<br />',$items);
                        } else {
	                        $logons=$data->lastLogin;
	                        if (is_object($logons))
	                        return Html::a('Создать',['/arms/create','user_id'=>$data->id]);
                         else
	                        return 'Нет';
                        }
                    }
                }
	        ],
	        [
		        'attribute'=>'LastThreeLogins',
		        'format'=>'raw',
		        'value' => function($data) use ($renderer){
			        $logons=$data->lastThreeLogins;
			        if (is_array($logons) && count($logons)) {
			            $items=[];
			            foreach ($logons as $logon) {
			                $items[]=$renderer->render('/login-journal/item-comp',['model'=>$logon]);
                        }
                        return implode('<br />',$items);
			        } else return null;
		        }
	        ],
            //'work_phone',
            //'Bday',
            //'manager_id',
            //'nosync',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
