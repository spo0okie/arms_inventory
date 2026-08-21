<?php
/**
 * Подсказка «нашлось оборудование с этим MAC» в карточке ОС без АРМ
 * (issue #218): у ОС есть MAC, АРМ не привязан, а в инвентаризации есть
 * оборудование с тем же адресом — почти наверняка это оно и есть.
 * Привязку не делаем молча: «привязать» открывает обычную форму правки
 * ОС с подставленным АРМ, решение и сохранение остаются за человеком.
 */

/* @var $this yii\web\View */
/* @var $model app\models\Comps */
/* @var $static_view bool */

use app\components\widgets\page\ModelWidget;
use yii\helpers\Html;

if (!isset($static_view)) $static_view=false;

//в статичном режиме (печать, тултипы) предлагать нечего - там нет действий
$candidates=$static_view?[]:$model->macArmCandidates();

if (count($candidates)) { ?>
	<div class="small mt-1">
		<span class="text-secondary">Оборудование с тем же MAC:</span>
		<?php foreach ($candidates as $tech) { ?>
			<span class="text-nowrap">
				<?= ModelWidget::widget(['model'=>$tech,'options'=>['static_view'=>true]]) ?>
				<?= Html::a('привязать',
					['/comps/update','id'=>$model->id,'Comps[arm_id]'=>$tech->id],
					[
						'class'=>'open-in-modal-form',
						'qtip_ttip'=>'Открыть правку ОС с этим АРМ — останется сохранить',
						'data-pjax'=>0,
					]) ?>
			</span>
		<?php } ?>
	</div>
<?php }
