<?php
/**
 * Превью скана
 */

use app\models\Scans;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Scans */
	$model_id=$model->id;

if (!isset($tile_class)) $tile_class='scans-thumb-tile';
if (!isset($img_class)) $img_class='img-thumbnail';

if (!isset($static_view)) $static_view=false;
?>
<div class="<?= $tile_class ?>" id="scan_thumb_tile_<?= $model_id ?>">
    <div class="<?= $img_class ?>">

        <?php if (!$model->fileExists) {
            echo Html::img(Scans::noThumb(),['title'=>"Ошибка: файл ".$model->shortFname." не обнаружен!"]);
        } else {
	        $hint = 'Файл в формате ' . strtoupper($model->format) . '. Размер ' . $model->humanFileSize;
			$img=$model->idxThumb;
			echo match ($img) {
				Scans::$NO_ORIG_ERR => Html::img(Scans::noThumb(), ['title' => "Ошибка: файл " . $model->shortFname . " не обнаружен!"]),
				Scans::$RENDERING_ERR => Html::img(Scans::noThumb(), ['title' => "Ошибка: файл " . $model->shortFname . " не удается отрисовать!"]),
				default => Html::a(
					Html::img($model->idxThumb,
						['title' => $hint]),
					$model->fullFname
				),
			};
        }

        if (!$static_view) { ?>
            <div class="scans-thumb-ctrls">
                <span class="fas fa-trash"  title="Удалить" onclick="scansDeleteConfirm(<?= $model_id ?>)"></span>
            </div>
        <?php } ?>
    </div>

</div>
