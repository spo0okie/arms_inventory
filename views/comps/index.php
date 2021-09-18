<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CompsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Операционные системы';
$this->params['breadcrumbs'][] = \app\models\Comps::$title;
$renderer=$this;
?>
<div class="comps-index">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= \app\components\HintIconWidget::widget(['model'=>'\app\models\Comps','cssClass'=>'pull-right']) ?>
    </h1>
    Информация об известных установках операционных систем и ПО.<br/>
    <div id="help_block" style="display: none">
    <p>
    
    </p>
    </div>

	<?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Еще раз обращаю внимание, что это делать надо только для тех компьютеров, на которых не запускается автоматический скрипт!']) ?>
	
	
	<?= $this->render('/comps/table', [
		'searchModel' => $searchModel,
		'dataProvider' => $dataProvider,
		'columns'   => ['name','ip','os','updated_at','arm_id','place','raw_version'],
	]) ?>
</div>
