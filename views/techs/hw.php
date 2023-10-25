<?php


/* @var $this yii\web\View */
/* @var $model app\models\Comps */
/* @var $manufacturers app\models\Manufacturers[] */

use app\models\HwListItem;
use app\models\Manufacturers;

if (!isset($manufacturers)) $manufacturers= Manufacturers::fetchNames();

if (!isset($static_view)) $static_view=false;
?>

<table>
	<?php if (!$static_view) { ?>
        <thead>
			<tr>
				<th>Производитель</th>
				<th>Продукт</th>
				<th>Серийный номер</th>
				<th>Инвентарный номер</th>
				<th class="passport_tools"></th>
			</tr>
		</thead>
	<?php
	}
	foreach ($model->hwList->items as $pos=>$item) {
		if (!$static_view || !$item->hidden) echo $this->render('/hwlist/item',
			compact('model','pos', 'item','manufacturers','static_view')
		);
	}

	if (!$static_view) {
        $item=new HwListItem();
        $item->type='custom';

        echo $this->render('/hwlist/item',
            [
                'model'=>$model,
                'item'=>$item,
                'pos'=>null,
                'manufacturers'=>$manufacturers,
                'addItem'=>true ,
            ]
        );
	}
	?>
</table>
