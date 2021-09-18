<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

if (!isset($static_view)) $static_view=false;
//если не передать отдельно набор привязанных армов, то отрендерятся те что привязаны к группе
//можно передать АРМы конкретной закупки
if (!isset($arms)) $arms=$model->arms;
if (!isset($arms_href)) $arms_href=['/lic-groups/unlink','id'=>$model->id];

$soft=$model->soft;

?>
<h4>
    Тип лицензирования:
    <?= Html::a($model->licType->descr,['/lic-types/view','id'=>$model->lic_types_id]) ?>
    <?= Html::a('<span class="fas fa-pencil-alt"/>',['/lic-types/update','id'=>$model->lic_types_id]) ?>
</h4>
<?php if (!$static_view) { ?>
    <p>
    	<?= Yii::$app->formatter->asNtext($model->licType->comment) ?>
    </p>
<?php } else echo '<br />' ?>

<br />
<?php if (!$static_view) { ?>
<div class="row">
    <div class="col-md-6">
<?php }  ?>

        <h4>Лицензируемые продукты:</h4>
        <p>
		    <?php foreach ($soft as $item) { ?>
			    <?= $this->render('/soft/item',['model'=>$item]) ?>
			    <?php if (!$static_view) echo Html::a('<span class="fas fa-trash"/>',[
			        '/lic-groups/unlink',
                    'id'=>$model->id,
                    'soft_id'=>$item->id,
                ],[
				    'data'=>['confirm' => 'Убрать ПО '.$item->descr.' из этой группы лицензий?',]
                ]) ?>
                <br />
		    <?php } ?>
        </p>

<?php if (!$static_view) { ?>
    </div>
    <div class="col-md-6">
<?php } else echo '<br />' ?>

        <h4>Привязанные АРМы:</h4>
        <p>
		    <?php foreach ($arms as $arm) { ?>
			    <?= $this->render('/arms/item',['model'=>$arm]) ?>
			    <?php if (!$static_view) echo Html::a('<span class="fas fa-trash"/>',array_merge(
                    ['arms_id'=>$arm->id],
                    $arms_href
                ),
                    ['data'=>['confirm' => 'Отвязать лицензию от АРМ '.$arm->num.'?',]]
			    ) ?>
                <br />
		    <?php } ?>
        </p>

<?php if (!$static_view) { ?>
    </div>
</div>
<?php } else echo '<br />' ?>
