<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ContractsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Contracts::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="contracts-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
	        [
		        'attribute'=>'fullname',
		        'header'=>'Документы',
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/contracts/item',['model'=>$data,'name'=>$data['sname']]);
		        }
	        ],
	        [
		        'attribute'=>'state_id',
		        'filter'=>\app\models\ContractsStates::fetchNames(),
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/contracts/item-state',['model'=>$data]);
		        }
	        ],
	        [
		        'attribute'=>'docsAttached',
		        'header'=>'<span class="glyphicon glyphicon-paperclip" title="Привязано документов"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return (count($data->childs)+($data->parent_id?1:0))?(count($data->childs)+($data->parent_id?1:0)):'';
		        }
	        ],
	        [
		        'attribute'=>'armsAttached',
		        'header'=>'<span class="fas fa-desktop" title="Привязано АРМов"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->arms)?count($data->arms):'';
		        }
	        ],
	        [
		        'attribute'=>'techsAttached',
		        'header'=>'<span class="fas fa-print" title="Привязано техники"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->techs)?count($data->techs):'';
		        }
	        ],
	        [
		        'attribute'=>'licsAttached',
		        'header'=>'<span class="fas fa-award" title="Привязано лицензий"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->licItems)?count($data->licItems):'';
		        }
	        ],
	        [
		        'attribute'=>'orgInetsAttached',
		        'header'=>'<span class="glyphicon glyphicon-globe" title="Привязано вводов интернет"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->orgInets)?count($data->orgInets):'';
		        }
	        ],
	        [
		        'attribute'=>'orgPhonesAttached',
		        'header'=>'<span class="glyphicon glyphicon-phone-alt" title="Привязано услуг телефонии"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->orgInets)?count($data->orgInets):'';
		        }
	        ],
        ],
    ]); ?>
</div>
