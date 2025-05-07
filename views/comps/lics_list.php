<?php
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */
/* @var $this yii\web\View */
/* @var $model app\models\Comps */

if (!isset($static_view)) $static_view=false;
if (!isset($glue)) $glue='<br />';

$output=[];

foreach ($model->licGroups as $licGroup)
	$output[]=$licGroup->renderItem($this,['static_view'=>$static_view]);
foreach ($model->licItems as $licItem)
	$output[]=$licItem->renderItem($this,['static_view'=>$static_view,'name'=>$licItem->dname]);
foreach ($model->licKeys as $licKey)
	$output[]=$licKey->renderItem($this,['static_view'=>$static_view,'name'=>$licKey->dname]);

if (count($output)) {
?>
		<div class="pe-5">
			<h4>Привязанные лицензии</h4>
			<p>
				<?= implode($glue,$output) ?>
			</p>
		</div>

<?php }