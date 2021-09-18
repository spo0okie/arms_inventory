<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.01.2019
 * Time: 3:25
 */



use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

if (!isset($static_view)) $static_view=false;

?>


<h1>

	<?= $model->name ?>
	<?php if (!$static_view) { ?>
		<?= Html::a('<span class="fas fa-pencil-alt"/>', ['update', 'id' => $model->id]) ?>
		<?= Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот ввод интернет? Это действие необратимо!',
				'method' => 'post',
			],
		]) ?>
	<?php } ?>
</h1>

<p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>
<?php if($model->cost) { ?>
	<p>
		Стоимость: <?= Yii::$app->formatter->asCurrency($model->cost) ?>
		<?php if ($model->charge){ ?>
			(в т.ч. НДС: <?= Yii::$app->formatter->asCurrency($model->charge) ?>)
		<?php } ?>
		/мес
	</p>
<?php }

if ($model->static) { ?>
	<h4>Статический адрес:</h4>
	<p>
		Адрес: <?= $model->ip_addr ?> <br />
		Маска: <?= $model->ip_mask ?> <br />
		Шлюз:  <?= $model->ip_gw ?> <br />
        <?php
            $dnses=[];
            if (strlen(trim($model->ip_dns1))) $dnses[]=trim($model->ip_dns1);
            if (strlen(trim($model->ip_dns2))) $dnses[]=trim($model->ip_dns2);
            if (count($dnses)) { ?>
                DNS: <?= implode(",",$dnses) ?>
            <?php }
        ?>
	</p>
<?php } else { ?>
	<h4>Динамический адрес</h4>
<?php } ?>

<h4>Тип подключения</h4>
<?= $model->type ?>

<h4>Место подключения:</h4>
<?= $this->render('/places/item',['model'=>$model->places ,'static_view'=>$static_view]) ?>

<h4><?= $model->getAttributeLabel('contracts_id')?> </h4>
<p><?= $this->render('/contracts/tree-map',['model'=>$model->contract,'static_view'=>$static_view,'map'=>$static_view?'chain-up':'full']) ?></p>

<h4><?= $model->getAttributeLabel('account')?> </h4>
<p><?= $model->account ?></p>


<hr />

<h4>Оператор связи:</h4>
<?= $this->render('/prov-tel/item',['model'=>$model->provTel ,'static_view'=>$static_view]) ?>

<?= $this->render('/prov-tel/card',['model'=>$model->provTel,'static_view'=>$static_view]) ?>
