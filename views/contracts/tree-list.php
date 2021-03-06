<?php
/**
 * Список документов
 * User: aareviakin
 * Date: 20.01.19
 * Time: 19-36
 */

/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $selected_id integer */


?>
<?= $this->render('item-chain',[
    'model'=>$model,
    'selected_id'=>$selected_id]
) ?>

	<ul class="ul-treefree ul-dropfree">
		<?php foreach ($model->chainChilds as $child) if (!$model->is_successor) { ?>
			<li>
				<?= $this->render('tree-list',[
					'model'=>$child,
					'selected_id'=>$selected_id,
				]); ?>
			</li>
		<?php } ?>
	</ul>



