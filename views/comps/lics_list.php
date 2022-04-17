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
	$output[]=$this->render('/lic-groups/item',['model'=>$licGroup,'static_view'=>$static_view]);
foreach ($model->licItems as $licItem)
	$output[]=$this->render('/lic-items/item',['model'=>$licItem,'static_view'=>$static_view,'name'=>$licItem->dname]);
foreach ($model->licKeys as $licKey)
	$output[]=$this->render('/lic-keys/item',['model'=>$licKey,'static_view'=>$static_view,'name'=>$licKey->dname]);

if (count($output)) {
?>
    <h4>Привязанные лицензии</h4>
	<p>
		<?= implode($glue,$output) ?>
	</p>

<?php }