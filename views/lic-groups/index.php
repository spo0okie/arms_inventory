<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LicGroupsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\LicGroups::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="lic-groups-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //'descr',
	        [
		        'attribute'=>'descr',
		        'format'=>'raw',
		        'value'=>function($item) use ($renderer){
			        return $renderer->render('/lic-groups/item',['model'=>$item]);
		        }
	        ],
	        [
		        'attribute'=>'itemsCount',
		        'header'=>'Закупок<br/>акт/всего',
		        'format'=>'raw',
		        'value'=>function($item) {
			        return $item->activeItemsCount.'/'.count($item->licItems);
		        }
	        ],
	        [
		        'attribute'=>'itemsCount',
		        'header'=>'Ключей<br/>исп/всего',
		        'format'=>'raw',
		        'value'=>function($item) {

			        return $item->usedCount.'/'.$item->activeCount;
		        }
	        ],
            'comment:ntext',
            //'created_at',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
