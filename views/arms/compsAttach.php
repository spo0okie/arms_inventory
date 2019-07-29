<?php
$script = <<<JS
    function attachCompToArm(comp_id,arm_id) {
        $.ajax({
            url: '/web/comps/update?id='+comp_id,
            type: 'POST',
             data: { 'Comps[arm_id]' : arm_id },
             success: function(data) {
                console.log(data[0].id+' -> '+data[0].arm_id);
                 if (typeof data[0].id != 'undefined'){
                     if (data[0].arm_id == arm_id) {
                         $('#arms-comp_id').append('<option value="'+data[0].id+'">'+data[0].name+'</option>');
                         $('#comp-attach-selector'+data[0].id).hide();
                     }
                 }
             }
         });
    }
JS;

$this->registerJs($script, \yii\web\View::POS_HEAD);
        

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/**
 * Кусочек кода который генерист список логинов выбранного пользователя для приаттачивания машин к АРМу
 */

/* @var $this yii\web\View */
/* @var $user_id integer */
/* @var $arm_id integer */
/* @var $form yii\widgets\ActiveForm */


if (is_null($arm_id)) {
	?>
	<span class="disabled">
		<span class="glyphicon glyphicon-warning-sign"></span>
		Чтобы привязывать ОС к АРМ сначала надо сохранить АРМ, иначе не к чему привязываться
	</span>

<?php } else {
//смотрим а есть ли пользователь у этой АРМ
	$user=\app\models\Users::findOne(['id'=>$user_id]);
	if (is_object($user)) {
		//у нас есть юзер - работаем

		//ищем логины
		if (is_array($logins=$user->lastThreeLogins) && count($logins)) {
			//перебираем логины
			foreach ($logins as $login) if ($comp_id=$login->comps_id) {
				//пропускаем компы уже привязанные к этому арму

				$comp=$login->comp;
				if (($arm_id==$comp->arm_id)) continue;

				if ($comp->arm_id) { ?>
					<span
                        id="comp-attach-selector<?= $comp_id ?>"
                        class="arms-comp-attach-selector"
                        qtip_ttip="Сейчас <?= $comp->name ?> привязан к <?= $comp->arm->num ?>"
                        onclick="attachCompToArm(<?= $comp_id ?>,<?= $arm_id ?>);"
                    >
                        Перепривязать <?= $comp->name ?>
                    </span>
				<?php } else { ?>
                    <span
                        id="comp-attach-selector<?= $comp_id ?>"
                        class="arms-comp-attach-selector"
                        qtip_ttip="Сейчас <?= $comp->name ?> не привязан ни к какому АРМ"
                        onclick="attachCompToArm(<?= $comp_id ?>,<?= $arm_id ?>);"
                    >
                        Привязать <?= $comp->name ?>
                    </span>
				<?php }
			}
		}
	}
}
?>

