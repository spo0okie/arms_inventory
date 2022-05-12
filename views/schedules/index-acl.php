<?php

use yii\helpers\Html;
use yii\grid\GridView;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Acls::$scheduleTitles;
$this->params['breadcrumbs'][] = $this->title;

$renderer=$this;
?>
<div class="schedules-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create-acl'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php //echo $this->render('_search', ['model' => $searchModel]); ?>

	
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
			[
				'attribute'=>'objects',
				'format'=>'raw',
				'value'=>function($data) use ($renderer) {
					$output=[];
					if (count($data->acls)) foreach ($data->acls as $acl) {
						foreach ($acl->aces as $ace) {
							//$output[]=$renderer->render('/aces/objects',['model'=>$ace,'glue'=>',']);
							$output[]=$renderer->render('/aces/objects',['model'=>$ace,'glue'=>'<br />']);
						}
					}
					//return implode(',',$output);
					return implode('<br />',$output);
				}
			],
			[
				'attribute'=>'resources',
                'format'=>'raw',
                'value'=>function($data) use ($renderer) {
    				$output=[];
    				if (count($data->acls)) foreach ($data->acls as $acl) {
    					$output[]=$renderer->render('/acls/item',['model'=>$acl,'static_view'=>true]);
					}
					return implode('<br />',$output);
	
				}
			],
			[
				'attribute'=>'name',
				'format'=>'raw',
				'value'=>function($data) use ($renderer) {
					$output=[Html::a($data->name,['view','id'=>$data->id])];
					if ($data->description) $output[]=$data->description;
					if ($data->history) $output[]=Markdown::convert($data->history);
					return implode('<br />',$output);
				}
			],
			[
				'attribute'=>'periods',
				'format'=>'raw',
				'value'=>function($data) use ($renderer) {
					$output=[];
					if (is_array($periods=$data->findPeriods(null,null)) && count($periods))
						foreach ($periods as $period) {
							$output[]=$period->periodSchedule;
							$output[]=$period->comment;
						}
						
					
					return implode('<br />',$output);
				}
			],
        ],
    ]); ?>


</div>
