<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model Networks */

use app\components\DynaGridWidget;
use app\components\ListObjectsWidget;
use app\components\ShowArchivedWidget;
use app\models\Aces;
use app\models\Comps;
use app\models\Networks;

//эта страничка вызывается из другой, где есть этот виджет,
//поэтому хак со сменой поведения архивных элементов по умолчанию делаем руками, а не автоматом
ShowArchivedWidget::$defaultValue=false;
$static_view=false;
$columns=include $_SERVER['DOCUMENT_ROOT'].'/views/aces/columns.php';
unset($columns['resource_nodes']);
$renderer=$this;
$columns['network_hosts']=[
	'value'=>function($data) use ($renderer,$model){
		if (is_object($acl=$data->acl)) {
			if (count($nodes=$acl->nodes)) {
				$hosts=[];
				foreach ($nodes as $node) {
					/** @var Comps $node */
					if (count($ips=$node->netIps)) {
						foreach ($ips as $ip) {
							if ($ip->isIn($model)) {
								$hosts[]=$ip->renderItem($renderer,['static_view'=>true])
									.':'
									.$node->renderItem($renderer,['static_view'=>true]);
							}
						}
					}
				}
				return ListObjectsWidget::widget([
					'models'=>$hosts,
					'title'=>false,
					'card_options'=>['cardClass'=>'m-0 p-0'],
					'lineBr'=>false,
					'raw_items'=>true,
					'glue'=>'<br/>',
				]);
				
			}
		}
		return '';
	}
];

?>
<div class="network-aces-index">
	<?= DynaGridWidget::widget([
		'id' => 'network-connections-list',
		'pageUrl'=>['/services/view','id'=>$model->id],
		'model' => new Aces(),
		'panel' => false,
		'columns' => $columns,
		//'defaultOrder' => ['initiator_service','initiator_nodes','initiator_details','comment','target_service','target_nodes','target_details',],
		//'filterModel' => $searchModel,
		'dataProvider' => $dataProvider,
		'toggleButtonGrid'=>[
			'label' => '<i class="fas fa-wrench fa-fw"></i>',
			'title' => 'Персонализировать настройки таблицы',
			'data-pjax' => false,
			'class' => 'd-none',
		],
		'gridOptions' => [
			'layout'=>'{dynagrid}{items}',
			'showFooter' => false,
			'pjax' => true,
			'pjaxSettings' => ['options'=>[
				'enablePushState'=>false,
				'enableReplaceState'=>false,
			]],
			'rowOptions'=>function($data){return[
				'class'=> ShowArchivedWidget::archivedClass($data),
				'style'=> ShowArchivedWidget::archivedDisplay($data),
			];}
		],
	]) ?>
</div>