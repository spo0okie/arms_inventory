<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Users::$title;
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
	        'orgStruct.name',
            //'Persg',
            //'Uvolen',
            'Login',
            'Email:email',
            'Phone',
	        [
		        'attribute'=>'Arms',
		        'format'=>'raw',
		        'value' => function($data){
                    $arms=$data->arms;
                    if (is_array($arms)) {
                        if (count($arms)==1) {
                            return $arms[0]->name;
                        } elseif (count($arms)==0) {
	                        return 'Не назначено';
                        } else {
	                        return 'Несколько.';
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
