<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.08.2019
 * Time: 21:55
 */


use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $models app\models\Materials[] */
/* @var $arms app\models\Arms[] */
/* @var $techs app\models\Techs[] */

$materials=[];
\yii\helpers\ArrayHelper::multisort($models,'typeName');
foreach ($models as $model) {
	if ($model->rest!=0) $materials[]=$model;
}

if (count($materials)) {
	//согласно https://github.com/spo0okie/arms_inventory/issues/14 выводим не поштучно а суммируем одинаковые
	$groups=[];
	foreach ($materials as $item) {
		$groups[$item->model][]=$item;
	}
		
		?>
    <tr>
        <td colspan="11">
            <div>
	            <?php foreach ($materials as $item) echo $this->render('/materials/item',['model'=>$item,'material'=>true,'rest'=>true]).'<br />'; ?>
				<hr>
	            <?php foreach ($groups as $group) echo $this->render('/materials/group',['models'=>$group,'material'=>true,'rest'=>true]).'<br />'; ?>
            </div>
        </td>
    </tr>
<?php } ?>