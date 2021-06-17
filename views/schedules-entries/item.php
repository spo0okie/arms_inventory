<?php
/**
 * Рендер элемента графика рабочего дня
 * Created by PhpStorm.
 * User: reviakin.a
 * Date: 18.10.2020
 * Time: 17:33
 */

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SchedulesEntries */

if (is_object($model)) { ?>

<span class="schedules-entries-item"
	  qtip_ajxhrf="<?= \yii\helpers\Url::to(['schedules-entries/ttip','id'=>$model->id]) ?>"
>
	<?= \yii\helpers\Html::a($model->schedule,['/schedules/view/','id'=>$model->schedule_id,'date'=>$model->date,'#'=>'day-'.$model->date]) ?>
</span>
<?php } else
    echo ' - график не определен -';

