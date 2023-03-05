<?php



/* @var $this yii\web\View */
/* @var $models \app\models\Places */

\yii\helpers\Url::remember();
$this->title = \app\models\Places::$title;
$this->params['breadcrumbs'][] = $this->title;

if (!isset($show_archived)) $show_archived=true;

echo $this->render('hdr_create_obj');
?>

<div class="places-index">

	<?php foreach ($models as $model) if (empty($model->parent_id)) {
		echo $this->render('container',['model'=>$model,'models'=>$models,'depth'=>0,'show_archived'=>$show_archived]);
	} ?>
    <br />
</div>
