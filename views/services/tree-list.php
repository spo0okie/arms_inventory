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


?>
<?= $this->render('item',['model'=>$model]) ?>
	<ul class="ul-treefree ul-dropfree">
		<?php foreach ($model->children as $child) { ?>
			<li><?= $this->render('tree-list',['model'=>$child]); ?></li>
		<?php } ?>
	</ul>



