<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';
?>
<div class="comps-ttip ttip-card">

    <h1>
        <?= Html::a(mb_strtolower($model->fqdn),['comps/view','id'=>$model->id]) ?>
        &nbsp;
        <?= Html::a('<span class="glyphicon glyphicon-log-in"/>','remotecontrol://'.$model->fqdn) ?>
    </h1>
	<?= $this->render('ips_list',['model'=>$model,'static_view'=>true]) ?><br />
    <p><?= $model->os ?></p>

    <h4>Последнее получение данных</h4>
    <p>
        <span class="update-timestamp"><?= $model->updated_at ?></span>
    </p>

    <h4>Журнал входов (3 посл)</h4>
    <div class="login_journal">
		<?php
		$logons=$model->lastThreeLogins;
		if (is_array($logons) && count($logons)) {
			$items=[];
			foreach ($logons as $logon) {
				echo $this->render('/login-journal/item-user',['model'=>$logon]).'<br />';
			}
		}?>
    </div>







</div>