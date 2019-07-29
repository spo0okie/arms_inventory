<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LoginJournal */
/* @var $name string */

if (is_object($model)) {
if (!isset($name)) $name=$model->compName

?>
<span class="login-journal-item">
    <?= Html::a($model->compName.' ('.$model->age.')','remotecontrol://'.$model->compFqdn, [
	    'qtip_ajxhrf'=>\yii\helpers\Url::to(['/login-journal/ttip','id'=>$model->id]),
        'qtip_class'=>"qtip-wide",
    ]) ?>
</span>
<?php } else echo "Отсутствует";