<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetIpsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $networkProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = app\models\NetIps::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="net-ips-index">
	<?php yii\widgets\Pjax::begin(['id' => 'net-ips-outer-div']);
	
	echo DynaGridWidget::widget([
		'id' => 'net-ips-index',
		'header' => Html::encode($this->title),
		'createButton' => Html::a('Новый', ['create'], ['class' => 'btn btn-success']),
		'columns' => require 'columns.php',
		'dataProvider' => $dataProvider,
		'gridOptions'=>[
			'pjax' => true,
			'pjaxSettings' => [
				'neverTimeout'=>true,
				'options'=>['id'=>'net-ips-outer-div'],
			],
		],
		'filterModel' => $searchModel,
	]) ;
	
	if (is_object($networkProvider)) {
		echo '<h4 class="mt-4 mb-2">Адрес не найден, но есть подходящая сеть</h4>';
		echo $this->render('/networks/table', ['dataProvider'=>$networkProvider, 'searchModel'=>null]);
	}
	yii\widgets\Pjax::end();
	?>
</div>
