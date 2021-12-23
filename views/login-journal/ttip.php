<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgPhones */
?>
<div class="login-journal-ttip">
    <?= DetailView::widget([
		'model' => $model,
		'attributes' => [
			'time',
			[
				'attribute'=>'user',
				'format'=>'raw',
				'title'=>'Пользователь',
				'value'=>function($data){
					if (is_object($user=$data->user))
					return Html::a($data->userDescr,['/users/view','id'=>$user->id]);
					return $data->userDescr;
				}
			],
			[
				'attribute'=>'Компьютер',
                'format'=>'raw',
				'value'=>function($data){
					if (is_object($comp=$data->comp))
						return Html::a($data->compName.'&nbsp;<span class="fas fa-eye"/>',['/comps/view','id'=>$comp->id]).
							'&nbsp;'.Html::a('<span class="fas fa-pencil-alt"/>',['/comps/update','id'=>$comp->id]).
							'&nbsp;'.Html::a('<span class="fas fa-log-in"/>','remotecontrol://'.$data->compFqdn);
					return $data->compName;
				}
			],
		],
	]) ?>

</div>
