<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\components\ShowArchivedWidget;
use app\models\<?= $generator->modelClass ?>;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
<?= !empty($generator->searchModelClass) ? "/* @var \$searchModel " . ltrim($generator->searchModelClass, '\\') . " */\n" : '' ?>
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $switchArchivedCount */


Url::remember();

$this->title = <?= $generator->modelClass ?>::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;

$filtered=false;
if (isset(Yii::$app->request->get()['<?= $generator->modelClass ?>Search'])) {
	foreach (Yii::$app->request->get()['<?= $generator->modelClass ?>Search'] as $field) if ($field) $filtered=true;
}

if (isset($switchArchivedCount)) {
	$switchArchivedDelta=$switchArchivedCount-$dataProvider->totalCount;
	if ($switchArchivedDelta>0) $switchArchivedDelta='+'.$switchArchivedDelta;
} else {
	$switchArchivedDelta=null;
}

?>
<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">
	<?= "<?=" ?> DynaGridWidget::widget([
		'id' => '<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index',
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success','title'=>'Добавить новый элемент']),
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\<?= $generator->modelClass ?>','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'toolButton'=> '<span class="p-2">'. ShowArchivedWidget::widget([
			'labelBadgeBg'=>$filtered?'bg-danger':'bg-secondary',
			'labelBadge'=>$switchArchivedDelta
		]).'<span>',
	]) ?>
</div>
