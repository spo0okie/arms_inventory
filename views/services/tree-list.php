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

use app\helpers\ArrayHelper;


?>
<?= $this->render('item',['model'=>$model]) ?>
	<ul class="ul-treefree ul-dropfree">
		<?php $items=$model->children;
		ArrayHelper::multisort($items,'name');
		foreach ($items as $item) { ?>
			<li><?= $this->render('tree-list',['model'=>$item]); ?></li>
		<?php } ?>
	</ul>



