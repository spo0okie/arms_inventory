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
						return Html::a($data->compName.'&nbsp;<span class="glyphicon glyphicon-eye-open"/>',['/comps/view','id'=>$comp->id]).
							'&nbsp;'.Html::a('<span class="glyphicon glyphicon-pencil"/>',['/comps/update','id'=>$comp->id]).
							'&nbsp;'.Html::a('<span class="glyphicon glyphicon-log-in"/>','remotecontrol://'.$data->compFqdn);
					return $data->compName;
				}
			],
		],
	]) ?>

</div>
