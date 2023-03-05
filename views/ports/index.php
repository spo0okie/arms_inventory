<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PortsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = app\models\Ports::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="ports-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
	
			[
				'attribute'=>'techs_id',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('/techs/item',['model'=>$data->tech]);
				}
			],
            [
                'attribute'=>'name',
                'format'=>'raw',
                'value'=>function($data) use ($renderer){
                    return $renderer->render('item',['model'=>$data]);
                }
            ],
            'comment:ntext',
			[
				'attribute'=>'link_techs_id',
				'format'=>'raw',
				'value'=>function($data) use ($renderer) {
					if (is_object($data->linkPort)) {
						return $renderer->render('/ports/item', [
							'model' => $data->linkPort,
							'include_tech' => true,
							'reverse' => true,
						]);
					} elseif (is_object($data->linkTech)) {
						return $renderer->render('/techs/item', ['model' => $data->linkTech]);
					}
				}
			],

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
