<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */

use yii\helpers\Html;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */

if (!isset($static_view)) $static_view=false;
$model_id=$model->id;
?>
<h4>Привязанные документы:</h4>
<div id="arms_<?= $model->id ?>_attached_contracts">
    <p>
        <?php if(is_array($contracts = $model->contracts) && count($contracts)) foreach ($contracts as $contract) {
            echo $this->render('/contracts/item',['model'=>$contract]).'<br />';
        } else { ?>
            отсутствуют<br />
        <?php }

        if (!$static_view) {
            //моздаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
            Modal::begin([
                'id'=>'arm_link_contract_modal',
				'size' => Modal::SIZE_LARGE,
				'title' => 'Выберите Документ'
            ]);
            echo $this->render('/contracts/_linkform');
            //закрываем форму
            Modal::end();

            Modal::begin([
                'id'=>'arm_new_contract_modal',
				'size' => Modal::SIZE_LARGE,
                'title' => '<h2>Добавление документа к АРМ</h2>',
            ]);
            $contract=new \app\models\Contracts();
            $contract->arms_ids=[$model->id];
            echo $this->render('/contracts/_form',['model'=>$contract]);
            Modal::end();


            $js = <<<JS

            $('#arm_link_contract_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
            $('#arm_new_contract_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
        
            $('#contracts-link-form').on('beforeSubmit', function(){
                console.log($('input[name=contracts_id]').val());
                $.ajax({
                    url: '/web/contracts/link-arm',
                    type: 'GET',
                    data: {
                        arms_id: $model_id,
                        id: $('select[name=contracts_id]').val()
                    },
                    success: function(res){
                        $('#arm_link_contract_modal').modal('toggle');
                        $('#arms_${model_id}_attached_contracts').load('/web/arms/contracts?id=${model_id}');
                    },
                    error: function(){alert('Error!');}
                });
                return false;
            });
    
            $('#contracts-edit-form').on('afterSubmit', function(){
                $('#arm_new_contract_modal').modal('toggle');
                $('#arms_${model_id}_attached_contracts').load('/web/arms/contracts?id=${model_id}');
            });
  
JS;

            $this->registerJs($js);

        ?>
        <a onclick="$('#arm_link_contract_modal').modal('toggle')" class="href">Привязать</a>
        /
        <a onclick="$('#arm_new_contract_modal').modal('toggle')" class="href">добавить новый</a>

        <?php } ?>

    </p>
</div>
