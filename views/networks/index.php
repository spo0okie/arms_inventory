<?php

use yii\helpers\Url;


/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */

Url::remember();

$this->title = app\models\Networks::$titles;
//крошки собираются автоматически в layout (views/layouts/main.php)
$panel=true;
?>
<div class="networks-index">
    <?= $this->render('table',compact('dataProvider','searchModel','panel','switchArchivedCount')) ?>
</div>
