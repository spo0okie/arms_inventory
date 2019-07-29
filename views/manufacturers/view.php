<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Manufacturers */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Производители', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$soft=$model->soft;
$dict=$model->dict;

?>
<div class="manufacturers-view">

    <h1>
        <?= Html::encode($this->title) ?>
	    <?= Html::a('<span class="glyphicon glyphicon-pencil" />',['update','id'=>$model->id]) ?>
        <?php if (!count($soft)&&!count($dict)) { ?>
	        <?= Html::a('<span class="glyphicon glyphicon-trash" />',
		        [
			        'delete',
			        'id'=>$model->id,
                ],[
			        'data'=>[
                        'method'=>'post',
                        'confirm'=>'Удалить этого производителя?',
                    ]
		        ])
	        ?>
        <?php } ?>
    </h1>

	<?= Html::encode($model->full_name) ?>

    <p>
	    <?= Html::encode($model->comment) ?>
    </p>

	<?php if (count($soft)||count($dict)) { ?>
		<p>
            <span class="glyphicon glyphicon-warning-sign"></span>
            Невозможно в данный момент удалить этого производителя, т.к. у него есть варианты написания или продукты (см. ниже)
        </p>
	<?php } ?>

    <br />

    <p>
    <h4>Варианты написания производителя:</h4>
        <?php foreach ($dict as $item) { ?>
            <span class="manufacturers-dict-item">
                <?= $item->word ?>
                <?= Html::a('<span class="glyphicon glyphicon-pencil" />',['/manufacturers-dict/update','id'=>$item->id]) ?>
                <?= Html::a('<span class="glyphicon glyphicon-trash" />',
                    [
                        '/manufacturers-dict/delete',
                        'id'=>$item->id,
                    ],[
                        'data'=>[
                            'method'=>'post',
                            'confirm'=>'Удалить этот варинат написания производителя?',
                        ],
                    ])
                ?>
            </span><br />
        <?php } ?>
	    <?= Html::a('Добавить вариант написания', ['manufacturers-dict/create','manufacturers_id'=>$model->id]) ?>
    </p>

    <br />

    <h4>Програмные продукты:</h4>

    <p>
		<?php foreach ($soft as $item) echo $this->render('/soft/item',['model'=>$item]).'<br />' ?>
    </p>
</div>
