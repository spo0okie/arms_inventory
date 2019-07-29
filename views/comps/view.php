<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Comps */

$domain = is_object($model->domain)?$model->domain->name:'- не в домене - ';

$this->title = 'ОС '.$domain.'\\'.strtolower($model->name);
$this->params['breadcrumbs'][] = ['label' => 'ОС', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\helpers\Url::remember();
$manufacturers=\app\models\Manufacturers::fetchNames();
$model->swList->sortByName();

?>
<div class="comps-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
    <span class="update-timestamp">Последнее обновление данных <?= $model->updated_at ?></span>
    </p>

    <p>
        <?= Html::a('Изменить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
        <h3>АРМ</h3>
    <p>
        <?php if (is_object($model->arm)) { ?>
            <?= \yii\helpers\Html::a($model->arm->num,['arms/view', 'id'=>$model->arm_id]) ?>
        <?php } else { ?>
            не назначен
        <?php } ?>
    </p>

    <?= $this->render('ips_list',['model'=>$model]) ?>

    <div class="hardware_settings">
        <h3>Железо</h3>
    <?php // echo '<pre>'; var_dump($model->getHardArray()); echo '</pre>'; die(0); ?>

    <?php if ($model->ignore_hw) echo "<p>Игнорируется.</p>"; else {
        echo '<table>';
        foreach ($model->getHardArray() as $item) {
            echo $this->render('/hwlist/item',
                compact('model','item', 'manufacturers')
            );
        }
        echo '</table>';
    } ?>
    </div>






    <div class="software_settings">
        <h3>Софт</h3>
        <?php // echo '<pre>'; var_dump($model->swList->items); echo '</pre>'; ?>
        <h4 id="ignored_toggle">Игнорируемый</h4><table>
        <?php foreach ($model->swList->items as $item) if ($item['ignored']) {
            echo $this->render('/swlist/item', compact('item', 'model'));
        } ?></table>

        <h4>Согласованный</h4><table>
        <?php foreach ($model->swList->items as $item) if (!$item['ignored'] && $item['agreed']) {
            echo $this->render('/swlist/item', compact('item', 'model'));
        } ?></table>

        <h4>Требующий согласования</h4><table>
        <?php foreach ($model->swList->items as $item) if (!$item['ignored'] && !$item['agreed']) {
            echo $this->render('/swlist/item', compact('item', 'model'));
        }?> </table>
        <h4>Не распознанный:</h4>
        <table>
            <?php if (is_array($model->swList->data)) foreach ($model->swList->data as $item) { ?>
                <?= $this->render('soft_item_unrecognized', compact('model','item')) ?>
            <?php } ?>
        </table>
    </div>

    <h3>Журнал входов</h3>

    <div class="login_journal">
		<?php
		$logons=$model->lastThreeLogins;
		if (is_array($logons) && count($logons)) {
			$items=[];
			foreach ($logons as $logon) {
				echo $this->render('/login-journal/item-user',['model'=>$logon]).'<br />';
			}
		}?>
    </div>


</div>