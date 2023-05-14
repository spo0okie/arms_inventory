<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */
/* @var $linksData \yii\data\ArrayDataProvider */

$renderer = $this;

if (!isset($static_view)) $static_view=false;
if (!isset($keys)) $keys=null;
if (!isset($linksData)) $linksData=null;
?>


    <br />

    <?php  $this->render('/lic-groups/card-att',[
        'model'=>$model,
        'static_view'=>$static_view,
		'licGroup'=>$model->licGroup,
        'unlink_href'=>['/lic-items/unlink','id'=>$model->id],
		'linksData'=>$linksData,
    ])  ?>


    <?php if (!$static_view) { ?>

    <h4>Лицензионные ключи:</h4>
    <?php
		echo GridView::widget([
			'dataProvider' => $keys,
			'columns' => [
				['class' => 'yii\grid\SerialColumn'],
				[
					'attribute' => 'key_text',
					'format' => 'raw',
					'value' => function ($item) use ($renderer, $static_view) {
						return $renderer->render('/lic-keys/item', ['model' => $item, 'static_view' => $static_view]);
					}
				],
				[
					'attribute' => 'links',
					'format' => 'raw',
					'value' => function ($item) use ($renderer) {
						$output = '';
						foreach ($item->arms as $arm)
							$output .= ' ' . $renderer->render('/techs/item', ['model' => $arm,'icon'=>true,'static_view'=>true]);
						foreach ($item->comps as $comp)
							$output .= ' ' . $renderer->render('/comps/item', ['model' => $comp,'icon'=>true,'static_view'=>true]);
						foreach ($item->users as $user)
							$output .= ' ' . $renderer->render('/users/item', ['model' => $user,'icon'=>true,'static_view'=>true]);
						return $output;
					}
				],
				'comment'
			],
		]);
		
		
		echo Html::a('Добавить ключ',
			['/lic-keys/create','LicKeys[lic_items_id]'=>$model->id],
			['class'=>'open-in-modal-form btn btn-success','data-reload-page-on-submit'=>1]
		);
	 
	}?>

<br />
<br />

<?= $this->render('/contracts/model-list',['model'=>$model,'static_view'=>$static_view,'link'=>'lics_ids']) ?>





<h4>Комментарий:</h4>
    <p>
		<?= Yii::$app->formatter->asNtext($model->comment) ?>
    </p>
