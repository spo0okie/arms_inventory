<?php
/**
 * Список документов
 * User: aareviakin
 * Date: 20.01.19
 * Time: 19-36
 */

/* @var $this yii\web\View */
/* @var $model app\models\Services */
/* @var $selected_id integer */
/* @var $master app\models\Services */


use app\helpers\ArrayHelper;

$items=$model->children;
ArrayHelper::multisort($items,'name');


echo $this->render('item',['model'=>$model,'crop_parent'=>isset($master)]);

if (count($items)) {?>
	<ul class="ul-treefree ul-dropfree m-0">
		<?php foreach ($items as $item) { ?>
			<li><?= $this->render('tree-list',['model'=>$item,'master'=>$model]); ?></li>
		<?php } ?>
	</ul>
<?php }




