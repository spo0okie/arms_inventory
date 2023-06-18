<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;


/* @var $this yii\web\View */
/* @var $model app\models\LicGroups */

if (!isset($static_view)) $static_view=false;


?>

<h3><?= $this->render('/lic-groups/item',['model'=>$model,'static_view'=>$static_view,'noDelete'=>false]) ?></h3>
<hr/>
<?= $this->render('/lic-types/descr',['model'=>$model->licType]) ?>
