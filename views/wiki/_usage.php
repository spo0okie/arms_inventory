<?php

use app\components\ItemObjectWidget;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\helpers\Html;

/** @var \yii\web\View $this */
/**
 * @var array $usage одно место использования: объект инвентаризации, его поле,
 *   вид ссылки 'kind' и цепочка включений 'via', по которой найдена ссылка
 *   (пустая - находка лежит в самом поле объекта)
 */
/** @var array $kindLabels подписи видов ссылок (WikiLinksScanner::KIND_*) */

if (!isset($kindLabels)) $kindLabels=[];

/** @var ArmsModel|null $model */
$model=$usage['model']??null;
$class=$usage['class']??null;

//имя сущности для подписи (у моделей ARMS - статический $titles)
$entity='';
if ($class) $entity=(property_exists($class,'titles') && $class::$titles)?
	$class::$titles:
	StringHelper::className($class);

//имя объекта может быть и колонкой, и вычисляемым (или отсутствовать вовсе)
try { $name=$model?$model->name:null; } catch (\Throwable $e) { $name=null; }
if (!strlen((string)$name)) $name='#'.($usage['id']??'?');

//метка поля: спрашиваем у модели, иначе показываем сырое имя атрибута
$attribute=$usage['attribute']??'';
try { $attributeLabel=$model?$model->getAttributeViewLabel($attribute):$attribute; }
catch (\Throwable $e) { $attributeLabel=$attribute; }
?>
<div>
	<?= $model?ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'noUpdate'=>true,
		'noDelete'=>true,
		'show_archived'=>true,
	]):Html::encode($name) ?>
	<span class="text-muted small">
		<?= Html::encode($entity) ?> &rarr; <?= Html::encode($attributeLabel) ?>
	</span>
	<?php if (isset($usage['kind']) && isset($kindLabels[$usage['kind']])) { ?>
		<span class="badge bg-secondary"><?= Html::encode($kindLabels[$usage['kind']]) ?></span>
	<?php } ?>
	<?php if (count($usage['via']??[])) { ?>
		<span class="text-muted small">
			через <?php foreach ($usage['via'] as $via) { ?>
				<code>{{page&gt;<?= Html::encode($via) ?>}}</code>
			<?php } ?>
		</span>
	<?php } ?>
	<?php if (($usage['title']??'')!=='') { ?>
		<span class="text-muted small">подпись: <?= Html::encode($usage['title']) ?></span>
	<?php } ?>
</div>
