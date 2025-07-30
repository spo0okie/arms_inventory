<?php

use app\components\DynaGridWidget;
use app\components\HintIconWidget;
use app\models\Services;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models Services[] */
/* @var $model Services */
/* @var $switchParentCount */
/* @var $switchArchivedCount */


$renderer=$this;



// Добавляем JS-код в страницу
$this->registerJs(<<<JS
    function adjustTreePrefixHeights() {
        $('.tree_col').each(function () {
            var \$cell = \$(this);
            var cellHeight = \$cell.height();
            var \$prefix = \$cell.closest('tr').find('.table-tree-prefix');
            if (\$prefix.length) {
                \$prefix.height(cellHeight);
            }
        });
    }

    $(document).ajaxComplete(function () {
        if (\$('.tree_col').length > 0) {
            adjustTreePrefixHeights();
        }
    });

    $(document).on('pjax:end', function () {
        adjustTreePrefixHeights();
    });

    $(window).on('resize', function () {
        adjustTreePrefixHeights();
    });

    if (window.ResizeObserver) {
        const resizeObserver = new ResizeObserver(() => {
            adjustTreePrefixHeights();
        });

        $(document).on('tableUpdated', function () {
            \$('.tree_col').each(function () {
                resizeObserver.observe(this);
            });
        });
    }
JS
);

?>

<div class="children-tree">

	<?= DynaGridWidget::widget([
		'id' => 'services-index',
		'model' => new Services(),
		'header' => Html::encode($this->title),
		'columns' => require 'columns.php',
		'defaultOrder' => ['name','sites','segment','providingSchedule','supportSchedule','responsible','compsAndTechs'],
		'createButton' => null,
		'hintButton' => HintIconWidget::widget(['model'=>'\app\models\Services','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'panel' => false,
		'toggleButtonGrid'=>[
			'label' => '<i class="fas fa-wrench fa-fw"></i>',
			'title' => 'Персонализировать настройки таблицы',
			'data-pjax' => false,
			'class' => 'd-none',
		],
		'gridOptions' => [
			'layout'=>'{dynagrid}{items}',
			'showFooter' => false,
			'pjax' => true,
			'pjaxSettings' => [
				'options'=>[
					'timeout'=>30000,
					'enablePushState'=>false,
					'enableReplaceState'=>false,
					//'linkSelector'=>'tr#service-connections-list-filters td input,thead.service-connections-list tr th a',
					//'linkSelector'=>'thead.service-connections-list tr th a'
					//'formSelector'=>'#service-connections-list-pjax form',
				]
			],
		],
	]) ?>
</div>

