<?php

use yii\helpers\Html;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

$model_id=$model->id;
?>
<p>
<?php if(is_array($contracts = $model->contracts) && count($contracts)) foreach ($contracts as $contract) {
        echo $this->render('/contracts/item', ['model' => $contract]) . '<br />';
} else { ?>
    отсутствуют<br />
<?php }


Modal::begin([
	'id'=>'lics_new_contract_modal',
	'size' => Modal::SIZE_LARGE,
	'header' => '<h2>Добавление документа к закупке лицензий</h2>',
	'toggleButton' => [
		'label' => 'Добавить новый документ',
		'tag' => 'a',
		'class' => 'href',
	],
	//'footer' => 'Низ окна',
]);
$contract=new \app\models\Contracts();
$contract->lics_ids=[$model->id];

echo $this->render('/contracts/_form',['model'=>$contract]);
Modal::end();

$this->registerJs("$('#contracts-edit-form').on('afterSubmit',function() {
    $('#lics_new_contract_modal').modal('toggle');
    $('#lics_${model_id}_attached_contracts').load('/web/lic-items/contracts?id=${model_id}');
})");

?>
</p>