<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\LicKeysSearch */

$this->title = \app\models\LicKeys::$title;
$this->params['breadcrumbs'][] = $this->title;

$renderer=$this;
?>
<div class="lic-keys-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
	    'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
	        //'licItem.sname',
	        [
				'attribute' => 'lic_item',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return  $renderer->render('/lic-items/item', ['model' => $data->licItem]);
				}
			],
	        [
		        'attribute' => 'key_text',
		        'format' => 'raw',
		        'value' => function ($data) use ($renderer) {
			        return  $renderer->render('/lic-keys/item', ['model' => $data]);
		        }
	        ],
	        [
		        'attribute' => 'arms_ids',
		        'format' => 'raw',
		        'value' => function ($item) use ($renderer) {
			        $output = '';
			        foreach ($item->arms as $arm)
				        $output .= ' ' . $renderer->render('/arms/item', ['model' => $arm]);
			        return $output;
		        }
	        ],
			
	        //'licItem.licGroup.name',
            //'key_text',
            'comment:ntext',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
