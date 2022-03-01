<?php

use yii\helpers\Html;
use yii\grid\GridView;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

if (!isset($static_view)) $static_view=false;
//если не передать отдельно набор привязанных армов, то отрендерятся те что привязаны к группе
//можно передать АРМы конкретной закупки
if (!isset($arms)) $arms=$model->arms;
if (!isset($arms_href)) $arms_href=['/lic-groups/unlink','id'=>$model->id];

$licItems=$model->licItems;
$soft=$model->soft;
$deleteable=!count($soft)&&!count($licItems);
$renderer=$this;

?>

<?php if (!$static_view) { ?>
<div class="row">
    <div class="col-md-6" >
<?php } ?>
        <h1>
			<?= Html::encode($model->descr) ?>
			<?= Html::a('<span class="fas fa-pencil-alt"/>', ['update', 'id' => $model->id]) ?>
			<?php if (!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
				'data' => [
					'confirm' => 'Действительно удалить '.\app\models\LicGroups::$title.'?',
					'method' => 'post',
				],
			]) ?>
        </h1>
        <p>
			<?= Yii::$app->formatter->asNtext($model->comment) ?>
        </p>

<?php if (!$static_view) { ?>
    </div>
    <div class="col-md-6" >
<?php } else echo '<br />' ?>

	    <?= $this->render('usage',['model'=>$model]) ?>

<?php if (!$static_view) { ?>
    </div>
</div>
<?php } ?>

<br />

<?= $this->render('card-att',compact(['model','static_view'])) ?>

<br />

<h4>Закупленные лицензии:</h4>
<p>
	<?php if ($static_view) {
        foreach ($model->licItems as $item) { ?>
            <?= $this->render('/lic-items/item',['model'=>$item,'static_view'=>$static_view]) ?><br />
        <?php }
    } else { ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                [
                    'attribute'=>'descr',
                    'format'=>'raw',
                    'value'=>function($item) use ($renderer){
                        return $renderer->render('/lic-items/item',['model'=>$item,'name'=>$item->descr]);
                    }
                ],
                'comment:ntext',
                'status'
            ],
        ]); ?>

        <span class="lic_item-item"><?= Html::a('Добавить закупку',['/lic-items/create','lic_group_id'=>$model->id],['class' => 'btn btn-success']) ?></span>
    <?php } ?>
</p>
