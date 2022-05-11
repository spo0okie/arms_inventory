<?php


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

$this->title = $model->descr;
$this->params['breadcrumbs'][] = ['label' => \app\models\LicGroups::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="lic-groups-view">
    <?= $this->render('card',compact(['model','dataProvider','searchModel','linksData'])) ?>
</div>
