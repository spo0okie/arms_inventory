<?php
/**
 * Список документов прикрепленных к модели
 * User: aareviakin
 * Date: 14.05.2023
 * Time: 14:56
 */

use yii\bootstrap5\Modal;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var \app\models\ArmsModel $model */

if (!isset($static_view)) $static_view=false;
if (!isset($link)) $link=$model::tableName().'_ids';

?>
<h4>Документы:</h4>
<p>
	
	<?php if(is_array($contracts = $model->contracts) && count($contracts)) foreach ($contracts as $contract) {
		echo $this->render('/contracts/item',['model'=>$contract]).'<br />';
	}
	
	if (!$static_view) {
		//создаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
		Modal::begin([
			'id'=>'link_contract_modal',
			'size' => Modal::SIZE_LARGE,
			'title' => 'Выберите связанный с оборудованием документ'
		]);
		echo $this->render('/contracts/_linkform');
		//закрываем форму
		Modal::end();
		
		
		$js = <<<JS

            $('#link_contract_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
            
            $('#contracts-link-form').on('beforeSubmit', function(){
                console.log($('input[name=contracts_id]').val());
                $.ajax({
                    url: '/web/contracts/link',
                    type: 'GET',
                    data: {
                        model_id: {$model->id},
                        link: "$link",
                        id: $('select[name=contracts_id]').val()
                    },
                    success: function(res){window.location.reload();},
                    error: function(){alert('Error!');}
                });
                return false;
            });
            
            $('#contracts-edit-form').on('afterSubmit', function(){window.location.reload();});
JS;
		
		$this->registerJs($js);
		
		?>
		<a onclick="$('#link_contract_modal').modal('toggle')" class="href">Привязать</a>
		/
		<?= Html::a('добавить новый',[
			'contracts/create','Contracts['.$link.'][]'=>$model->id
		],[
			'class'=>'open-in-modal-form',
			'data-reload-page-on-submit'=>1
		]) ?>
	
	<?php } ?>

</p>
