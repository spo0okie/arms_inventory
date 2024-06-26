<?php

/*
 * Содержимое формы вынесено в отдельный файл, т.к. может быть использовано и в форме ACE и в форме ACL
 */

use app\models\AccessTypes;
use app\models\Comps;
use app\models\Services;
use app\models\Users;
use yii\helpers\Html;
use \app\helpers\FieldsHelper;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

/*
 * Задача функции передать в контроллер список выбранных типов доступа
 * в ответ получить список типов доступа которые должны быть выбраны (могут появиться дополнительные)
 * которые не могут быть сняты (входят в состав комплексных типов доступа), список параметров для типов доступа
 */
/** @noinspection JSUnusedLocalSymbols */
$js= <<<JS
function updateAccessTypes() {
    let get_params=[];
    
    //получаем список выбранных типов
 	$('input[name="Aces[access_types_ids][]"]:checked').each(function(i,el){
 	    get_params.push('access_types_ids[]='+$(el).val());
 	});
 	//get_params.push('id='+{$model->id});
 	//перебираем IP параметры на предмет не сняли ли галки с их типов досутпа
 	$('div#ace-ip-params').children().each(function (i,el){
 	    let \$el=$(el);
 	    let type_id=\$el.data('type-id');
 	    if (type_id) {
 	        if ($('input[name="Aces[access_types_ids][]"][value='+type_id+']').prop('checked')) {
 	            \$el.show();
 	        } else {
 	            \$el.hide();
 	        }
 	    }
 	});
 	//передаем в контроллер с типами доступа
 	$.ajax({
 		url:'/web/aces/access-types-form?'+get_params.join('&'),
 	    success: function (data) {
 	    	//console.log(data);
 	    	for (let i in data) if (data.hasOwnProperty(i)) {
 	    	    //console.log(i);
 	    	    let type=data[i];
 	    	    //console.log(type);
 	    	    if (type.hasOwnProperty('optional')) {
					$('input[name="Aces[access_types_ids][]"][value='+i+']')
						.prop('checked',true)
						.prop('disabled',!(type.optional));
 	    	    }
				if (type.hasOwnProperty('is_ip')) {
					let \$param=$('div#access_type_'+i+'_param');
					if (!\$param.length) {
					    let value='';
						if (type.hasOwnProperty('default_param')) {
							value=type.default_param;
						}
						\$param=$('<div class="input-group" id="access_type_'+i+'_param" data-type-id="'+i+'">'+
							'<span class="input-group-text" id="access_type_'+i+'_param_label">IP Параметры '+type.name+'</span>'+
							'<input type="text" class="form-control" '+
							'name="Aces[ipParams]['+i+']" '+
							'value="'+value+'" '+
							'aria-describedby="access_type_'+i+'_param_label"></div>');
						$('div#ace-ip-params').append(\$param);
					}
				}
 	    	}
 	    }
 	});
 }
JS;
$this->registerJs($js, View::POS_HEAD);
//вызываем нашу функцию после загрузки формы, т.к. может быть нужно поотключать некоторые чекбоксы если они дочерние доступы
$this->registerJs('updateAccessTypes()');
?>

	<div class="row">
		<div class="col-md-6">
			<div class="card bg-light">
				<div class="card-header">Кому предоставляется доступ</div>
				<div class="card-body">
					<?= FieldsHelper::Select2Field($form, $model, 'users_ids', [
						'data' => Users::fetchWorking(),
						'itemsHintsUrl'=> Url::to(['/users/ttip','q'=>'dummyVar']),
						'pluginOptions' => ['dropdownParent' => $modalParent,'multiple' => true],
					]) ?>
					
					<?= FieldsHelper::Select2Field($form, $model, 'comps_ids', [
						'data' => Comps::fetchNames(),
						'pluginOptions' => ['dropdownParent' => $modalParent,'multiple' => true],
					]) ?>
					
					<?= FieldsHelper::Select2Field($form, $model, 'services_ids', [
						'data' => Services::fetchNames(),
						'pluginOptions' => ['dropdownParent' => $modalParent,'multiple' => true],
					]) ?>
					
					<?= FieldsHelper::TextAutoresizeField($form,$model,'ips',['lines' => 1]) ?>
					
					<?= FieldsHelper::TextInputField($form,$model, 'comment') ?>
				</div>
			</div>



		</div>
		<div class="col-md-6">
			<div class="card bg-light mb-3">
				<div class="card-header">Зачем предоставляется доступ</div>
				<div class="card-body">
					<?= FieldsHelper::TextInputField($form,$model, 'name');?>
				</div>
			</div>
			<!-- https://www.yiiframework.com/doc/api/2.0/yii-helpers-basehtml#activeCheckboxList()-detail -->
			<div class="card bg-light">
				<div class="card-header"><?= Html::tag(
						'span',
						'Какой этим объектам предоставляется доступ',
						FieldsHelper::toolTipOptions(
							'Типы предоставляемого доступа' ,
							$model->getAttributeHint('access_types_ids')
						)
					)?>
				</div>
				<div class="card-body">
					<?= FieldsHelper::CheckboxListField($form,$model, 'access_types_ids',[
						'data'=> AccessTypes::fetchNames(),
						'onchange'=>'updateAccessTypes()',
					]);?>
					<div id="ace-ip-params">
						<?php foreach ($model->accessTypes as $type) if ($type->is_ip) { ?>
							<div class="input-group" id="access_type_<?= $type->id ?>_param" data-type-id="<?= $type->id ?>">
								<span class="input-group-text" id="access_type_<?= $type->id ?>_param_label">IP Параметры <?= $type->name ?></span>
								<!--suppress HtmlFormInputWithoutLabel -->
								<input type="text" class="form-control" name="Aces[ipParams][<?= $type->id ?>]" value="<?= $model->getIpParams()[$type->id]??$type->ip_params_def ?>" aria-describedby="access_type_<?= $type->id ?>_param_label">
							</div>
						<?php }	?>
					</div>
				</div>
			</div>
			<hr />
			<?= FieldsHelper::MarkdownField($form,$model, 'notepad',['height'=>100]) ?>
		</div>
		<?= $form->field($model,"acls_id")->hiddenInput()->label(false)->hint(false) ?>
	</div>
	

