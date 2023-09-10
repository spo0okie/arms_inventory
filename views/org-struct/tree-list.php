<?php
/**
 * Один уровень древовидного списка помещений
 * User: aareviakin
 * Date: 24.11.2018
 * Time: 10:52
 */

/* @var $this yii\web\View */
/* @var $models app\models\OrgStruct */
/* @var $parent_id integer */
/* @var $tree_level integer */
if (!isset($tree_level)) $tree_level=0;
if (!isset($org_id)) $org_id=1;

$filtered=[];
//echo $parent_id;
//перебираем все модельки и отфильтровываем нужные нам
foreach ($models as $model) {
	if ($model->pup == $parent_id) {
		$filtered[]=$model;
	}
}

if (count($filtered)) {
	//если чтото нафильтровали то вперед!
?>
	<ul class="orgStruct_tree orgStruct_tree_lev_<?= $tree_level ?>">
		<?php foreach ($filtered as $model) {
			//рисуем элемент ?>
			<li>
                <?= $this->render('item',['model'=>$model,'static_view'=>false]) ?>
				<?= \yii\helpers\Html::a(
					'<span class="fas fa-plus-circle"></span>',
					['org-struct/create','OrgStruct[pup]'=>$model->id],
					['qtip_ttip'=>'Добавить дочернее подразделение']
				) ?>
                <?= $subtree=$this->render('tree-list',[
                    'models'=>$models,
                    'parent_id'=>$model->id,
                    $tree_level+1
                ]); ?>
            </li>
		<?php } ?>
	</ul>



<?php
}
