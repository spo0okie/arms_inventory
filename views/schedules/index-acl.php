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
					$items=[];
					if (count($data->acls)) foreach ($data->acls as $acl) {
						foreach ($acl->aces as $ace) {
							foreach ($ace->users as $user)
								$items[$user->shortName]=$this->render('/users/item',['model'=>$user,'static_view'=>true,'icon'=>true,'short'=>true]);
							
							foreach ($ace->comps as $comp)
								$items[$comp->name]=$this->render('/comps/item',['model'=>$comp,'static_view'=>true,'icon'=>true]);
							
							foreach ($ace->netIps as $ip)
								$items[$ip->sname]=$this->render('/net-ips/item',['model'=>$ip,'static_view'=>true,'icon'=>true,'no_class'=>true]);
							
							if (strlen($ace->comment))
								$items[$ace->comment]=$ace->comment;
							
							ksort($items,SORT_STRING);
						}
					}
					ksort($items,SORT_STRING);
					
					//return implode(',',$output);
					return implode('<br />',$items);
				}
			],
			[
				'attribute'=>'resources',
                'format'=>'raw',
                'value'=>function($data) use ($renderer) {
    				$output=[];
    				if (count($data->acls)) foreach ($data->acls as $acl) {
    					$output[$acl->sname]=$renderer->render('/acls/resource',['model'=>$acl,'static_view'=>true]);
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
					/**
					 * @var $data \app\models\Schedules
					 */
					$output=[
						date('Y-m-d').' '.date('H:i'),
					];
					if (is_array($periods=$data->findPeriods(null,null)) && count($periods))
						foreach ($periods as $period) {
							$output[]='<span title="'.Yii::$app->formatter->asNtext($period->comment).'">'.
								$period->periodSchedule.
								'</span>';
						}
						
					
					return implode('<br />',$output);
				},
				'contentOptions'=>function ($data) {
					/**
					 * @var $data \app\models\Schedules
					 */
					$working=$data->isWorkTime( date('Y-m-d'),date('H:i:s'));
					return [
						'class'=>$working?'table-success':'table-danger',
					];
				}
			],
        ],
    ]); ?>


</div>
