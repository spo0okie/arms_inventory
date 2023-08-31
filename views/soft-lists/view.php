<?php

use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\SoftLists */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = $model->descr;
$this->params['breadcrumbs'][] = ['label' => 'Списки ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="soft-lists-view">

    <?= $this->render('card',['model'=>$model]) ?>

    <h3>Содержимое списка:</h3><p>
        <?php
		echo GridView::widget([
			'dataProvider' => $dataProvider,
			'filterModel' => $searchModel,
			'columns' => [
				//['class' => 'yii\grid\SerialColumn'],
		
				//'id',
				/*            [
								'attribute'=>'manufacturers_id',
								'format'=>'raw',
								'value' => function($data) use ($manufacturers) {return isset($manufacturers[$data['manufacturers_id']])?$manufacturers[$data['manufacturers_id']]:'производитель не найден';}
							],*/
			
				[
					'attribute'=>'descr',
					'format'=>'raw',
					'value'=>function($data) use ($renderer){
						return $renderer->render('/soft/item',[
							'model'=>$data,
							'name'=>$data->name
						]);
					}
				],
				'comment',
				//'items:ntext',
				//'created_at:date',
		
				//['class' => 'yii\grid\ActionColumn'],
			],
		]);


		/*if (is_array($model->soft)&&count($model->soft)) { ?>
            <table class="table table-bordered table-striped">
            <?php
            $sortlist=[];
            foreach ($model->soft as $item) $sortlist[$item->fullDescr]=$item;
            ksort($sortlist);
            foreach ($sortlist as $item) { ?>
                <tr>
                    <td>
                        <?= \yii\helpers\Html::a($item->manufacturer->name, ['manufacturers/view', 'id' => $item->manufacturers_id]) ?>
                    </td>
                    <td>
	                    <?= \yii\helpers\Html::a(
		                    $item->descr,
		                    ['/soft/view', 'id' => $item->id],
		                    ['title' => 'Перейти к программному продукту']
	                    ) ?>
	                    <?= \yii\helpers\Html::a(
		                    '<span class="fas fa-pencil-alt"></span>',
		                    ['/soft/update', 'id' => $item->id],
		                    ['class'=>'passport_tools','title'=>'Редактировать програмный продукт']
	                    ) ?>
                    </td>
                    <td>
                        <?= $item->comment ?>
                    </td>
                </tr>

            <?php } ?>
            </table>
        <?php } else { ?>
            Отсутствует
        <?php } */ ?>
    </p>

</div>
