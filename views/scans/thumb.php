<?php
/**
 * Превью скана
 */
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Scans */
	$model_id=$model->id;

if (!isset($contracts_id)) $contracts_id=null;
if (!isset($static_view)) $static_view=false;
?>
<div class="scans-thumb-tile" id="scan_thumb_tile_<?= $model_id ?>">
    <div class="scans-thumb-img">

        <?php if (!$model->fileExists) {
            echo Html::img(\app\models\Scans::noThumb(),['title'=>"Ошибка: файл ".$model->shortFname." не обнаружен!"]);
        } else {
	        $hint = 'Файл в формате ' . strtoupper($model->format) . '. Размер ' . $model->humanFileSize;
	        echo Html::a(
		        Html::img((strtolower($model->format) == 'pdf') ? (\app\models\Scans::pdfThumb()) : $model->idxThumb,
                    ['title'=>$hint]),
		        $model->fullFname
	        );
        }

        if (!$static_view) { ?>
            <div class="scans-thumb-ctrls">
                <?php // Html::a('<span class="fas fa-pencil-alt" title="Редактировать"/></a>',['scans/update','id'=>$model->id])?>
                <span class="fas fa-trash"  title="Удалить" onclick="scansDeleteConfirm(<?= $model->id ?>)" />
            </div>
        <?php } ?>
    </div>
    <div class="scans-thumb-text">
		<?= $model->descr ?>
    </div>

</div>
