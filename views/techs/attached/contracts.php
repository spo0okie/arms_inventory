<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 03.10.2019
 * Time: 23:09
 */

use yii\bootstrap5\Modal;
use yii\helpers\Html;

/* @var \app\models\Techs $model */

if (!isset($static_view)) $static_view=false;
?>
<h4>Документы:</h4>
<p>

    <?php if(is_array($contracts = $model->contracts) && count($contracts)) foreach ($contracts as $contract) {
        echo $this->render('/contracts/item',['model'=>$contract]).'<br />';
    } else { ?>
        отсутствуют<br />
    <?php }

    if (!$static_view) {
    //моздаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
        Modal::begin([
            'id'=>'tech_link_contract_modal',
			'size' => Modal::SIZE_LARGE,
            'title' => 'Выберите связанный с оборудованием документ'
        ]);
        echo $this->render('/contracts/_linkform');
        //закрываем форму
        Modal::end();
        

        $js = <<<JS

            $('#tech_link_contract_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
            $('#tech_new_contract_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
            
            $('#contracts-link-form').on('beforeSubmit', function(){
                console.log($('input[name=contracts_id]').val());
                $.ajax({
                    url: '/web/contracts/link-tech',
                    type: 'GET',
                    data: {
                        techs_id: {$model->id},
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
        <a onclick="$('#tech_link_contract_modal').modal('toggle')" class="href">Привязать</a>
        /
        <?= Html::a('добавить новый',[
			'contracts/create','Contracts[techs_ids][]'=>$model->id
		],[
			'class'=>'open-in-modal-form',
			'data-reload-page-on-submit'=>1
		]) ?>

    <?php } ?>

</p>
