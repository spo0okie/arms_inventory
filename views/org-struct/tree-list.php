<?php
/**
 * Один уровень дерева подразделений с назначенными им тегами
 * User: aareviakin
 * Date: 24.11.2018
 * Time: 10:52
 */

/* @var $this yii\web\View */
/* @var $models app\models\OrgStruct[] */
/* @var $tree_level integer */

use yii\helpers\Html;
use app\components\TagsWidget;
use app\components\widgets\page\ModelWidget;

if (!isset($tree_level)) $tree_level=0;

if (count($models)) {?>
	<ul class="orgStruct_tree orgStruct_tree_lev_<?= $tree_level ?>">
		<?php foreach ($models as $model) {
			$children=$model->children;
			//рисуем элемент ?>
			<li>
                <?= ModelWidget::widget(['model'=>$model]) ?>
				<?= TagsWidget::widget(['model'=>$model]) ?>
				<?= Html::a(
					'<span class="fas fa-plus-circle"></span>',
					['org-struct/create','OrgStruct[parent_hr_id]'=>$model->hr_id,'OrgStruct[org_id]'=>$model->org_id],
					['qtip_ttip'=>'Добавить дочернее подразделение']
				) ?>
                <?= count($children)?$this->render('/org-struct/tree-list',[
                    'models'=>$children,
                    'tree_level'=>$tree_level+1
                ]):'' ?>
            </li>
		<?php } ?>
	</ul>



<?php
}
