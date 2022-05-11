<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Modal;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */
/* @var $linksData \yii\data\ArrayDataProvider */

if (!isset($static_view)) $static_view=false;
if (!isset($keys)) $keys=null;
$renderer=$this;
$contracts=$model->contracts;
$arms=$model->arms;
$deleteable=!count($arms)&&!count($contracts);
?>
<?php if (!$static_view) { ?>
    <div class="row">
        <div class="col-md-9" >
<?php } else echo '<h4>Группа лицензий: </h4>' ?>
            <h3>
                <?= $this->render('/lic-groups/item',['model'=>$model->licGroup,'static_view'=>$static_view]) ?>

                <?= $static_view?'<br /> <h4> Закупка: </h4>':'/' ?>

                <?= $this->render('/lic-items/item',['model'=>$model,'static_view'=>$static_view,'name'=>$model->descr]) ?>

                <?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
                    'data' => [
                        'confirm' => 'Удалить эту закупку лицензий? Это действие необратимо!',
                        'method' => 'post',
                    ],
                ]); else { ?>
					<span class="small">
						<span class="fas fa-lock"	title="Невозможно в данный момент удалить эту закупку лицензий, т.к. присутствуют привязанные объекты: документы или АРМы."></span>
					</span>
				<?php } ?>
            </h3>
			<hr/>
			<?= $this->render('/lic-types/descr',['model'=>$model->licGroup->licType]) ?>

    <?php if (!$static_view) { ?>
        </div>
        <div class="col-md-3" >
    <?php } else echo '<br />' ?>
            <h4>Статус:</h4>
            <h3><?= $model->status ?></h3>
            (<?= $model->datePart ?>)
	
			<?php if (count($model->arms_ids)) { ?>
				<br />Привязано к АРМ: <?= count($model->arms_ids) ?>
			<?php } ?>
			<?php if (count($model->comps_ids)) { ?>
				<br />Привязано к ОС: <?= count($model->comps_ids) ?>
			<?php } ?>
			<?php if (count($model->users_ids)) { ?>
				<br />Привязано к Польз: <?= count($model->users_ids) ?>
            <?php } ?>
	        <?php if (count($model->keys)) { ?>
                <br/>Внесено ключей: <?= count($model->keys) ?>
		        <?php if (count($model->keyArms)) { ?>
                    <br/>Распределено ключей: <?= count($model->usedKeys) ?>
		        <?php }
	        }?>
    <?php if (!$static_view) { ?>
        </div>
    </div>
    <?php } ?>

    <br />

    <?= $this->render('/lic-groups/card-att',[
        'model'=>$model,
        'static_view'=>$static_view,
		'licGroup'=>$model->licGroup,
        'unlink_href'=>['/lic-items/unlink','id'=>$model->id],
		'linksData'=>$linksData,
    ]) ?>


    <?php if (!$static_view) { ?>

    <h4>Лицензионные ключи:</h4>
    <?php
		echo GridView::widget([
			'dataProvider' => $keys,
			'columns' => [
				['class' => 'yii\grid\SerialColumn'],
				[
					'attribute' => 'key_text',
					'format' => 'raw',
					'value' => function ($item) use ($renderer, $static_view) {
						return $renderer->render('/lic-keys/item', ['model' => $item, 'static_view' => $static_view]);
					}
				],
				[
					'attribute' => 'links',
					'format' => 'raw',
					'value' => function ($item) use ($renderer) {
						$output = '';
						foreach ($item->arms as $arm)
							$output .= ' ' . $renderer->render('/arms/item', ['model' => $arm,'icon'=>true,'static_view'=>true]);
						foreach ($item->comps as $comp)
							$output .= ' ' . $renderer->render('/comps/item', ['model' => $comp,'icon'=>true,'static_view'=>true]);
						foreach ($item->users as $user)
							$output .= ' ' . $renderer->render('/users/item', ['model' => $user,'icon'=>true,'static_view'=>true]);
						return $output;
					}
				],
				'comment'
			],
		]);

	    Modal::begin([
			'id'=>'keys_add_modal',
			'size' => Modal::SIZE_LARGE,
			'title' => '<h2>Добавление лиц. ключа</h2>',
			'toggleButton' => [
				'label' => 'Добавить ключ',
				'tag' => 'button',
				'class' => 'btn btn-success',
			],
		]);

		$newKey = new \app\models\LicKeys();
		$newKey->lic_items_id = $model->id;
		echo $this->render('/lic-keys/_form',	['model'=>$newKey]);

		Modal::end();
	}?>


    <div id="lics_<?= $model->id ?>_attached_contracts">
        <h4>Привязанные документы:</h4>
        <p>
	    	<?= $this->render('contracts',['model'=>$model]) ?>
        </p>
    </div>

    <br />




<h4>Комментарий:</h4>
    <p>
		<?= Yii::$app->formatter->asNtext($model->comment) ?>
    </p>
