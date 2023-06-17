<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */
/* @var $dataProvider \yii\data\ActiveDataProvider */
/* @var $searchModel \app\models\LicGroupsSearch */
/* @var $linksData \yii\data\ArrayDataProvider */

if (!isset($static_view)) $static_view=false;
//если не передать отдельно набор привязанных армов, то отрендерятся те что привязаны к группе
//можно передать АРМы конкретной закупки
if (!isset($arms)) $arms=$model->arms;
if (!isset($arms_href)) $arms_href=['/lic-groups/unlink','id'=>$model->id];

$licItems=$model->licItems;
$soft=$model->soft;
$deleteable=!count($soft)&&!count($licItems);
$renderer=$this;
$licGroup=$model;

if (!isset($linksData)) $linksData=null;

?>

<?php if (!$static_view) { ?>
<div class="row">
    <div class="col-md-9" >
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
		<?= $this->render('/lic-types/descr',['model'=>$model->licType]) ?>

<?php if (!$static_view) { ?>
    </div>
    <div class="col-md-3" >
<?php } else echo '<br />' ?>

	    <?= $this->render('usage',['model'=>$model]) ?>
		<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>

<?php if (!$static_view) { ?>
    </div>
</div>
<?php } ?>

<br />

<?= $this->render('card-att',compact(['model','static_view','linksData'])) ?>

<br />

	<?php if ($static_view) { ?>
	<h4>Закупленные лицензии:</h4>
	<p>
		<?php foreach ($model->licItems as $item) { echo $this->render('/lic-items/item',['model'=>$item,'static_view'=>$static_view]).'<br />';} ?>
	</p>
    
    <?php } else { ?>
        <?= DynaGridWidget::widget([
			'id' => 'lic-groups-view-items',
			'header' => 'Закупленные лицензии',
			'columns' => \app\helpers\ArrayHelper::filter(include $_SERVER['DOCUMENT_ROOT'].'/views/lic-items/columns.php',[1,2,3]),
			'createButton' => Html::a('Добавить закупку',['/lic-items/create','lic_group_id'=>$model->id],['class' => 'btn btn-success']),
			'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\LicItems','cssClass'=>'btn']),
			'dataProvider' => $dataProvider,
			'filterModel' => $searchModel,
		]) ?>
	<?php } ?>
