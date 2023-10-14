<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\helpers\ArrayHelper;
use app\models\LicGroupsSearch;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel LicGroupsSearch */
/* @var $linksData ArrayDataProvider */

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
			'columns' => ArrayHelper::filter(include $_SERVER['DOCUMENT_ROOT'].'/views/lic-items/columns.php',[1,2,3]),
			'createButton' => Html::a('Добавить закупку',['/lic-items/create','LicItems'=>['lic_group_id'=>$model->id]],['class' => 'btn btn-success']),
			'hintButton' => HintIconWidget::widget(['model'=>'\app\models\LicItems','cssClass'=>'btn']),
			'dataProvider' => $dataProvider,
			'filterModel' => $searchModel,
		]) ?>
	<?php } ?>
