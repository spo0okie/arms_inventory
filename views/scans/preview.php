<?php
/**
 * Превью файла
 */

/* @var $this yii\web\View */
/* @var $model app\models\Scans */
//$view_max_height=800;


if (!isset($show_preview)) $show_preview=false;
if (isset($show_preview_max_size)) $show_preview=$model->fileSize<$show_preview_max_size;
if (!$model->fileExists) {
/*
 * Ситуация когда файл не найден
 */
?>

    <span class="fas fa-unlink"></span>
    Ошибка: файл <?= $model->shortFname ?> не обнаружен!

<?php
} else {
    echo 'Файл в формате '.strtoupper($model->format).'. Размер '.$model->humanFileSize.'<br />';
    if (strtolower($model->format)=='pdf') {
		/*
		 * Скан документа в формате PDF
		 */

		//если нам сразу можно показывать превью
            if ($show_preview) { ?>
                <iframe id="scan-preview-pdf-<?= $model->id ?>" class="scan-pdf-preview" src="<?= $model->fullFname ?>"></iframe>
            <?php } else { ?>

            <button type="button" class="btn btn-default" onclick="$('#scan-preview-pdf-<?= $model->id ?>').show().attr('src','<?= $model->fullFname ?>');">
                <span class="far fa-eye"></span>
                Показать
            </button>
            <a class="btn btn-default" href="<?= $model->fullFname?>">
                <span class="fas fa-floppy-save"></span>
                Скачать
            </a>

            <br />

            <iframe id="scan-preview-pdf-<?= $model->id ?>" class="scan-pdf-preview" style="display: none"></iframe>

        <?php }
    } else {
		/*
		 * Скан документа в формате изображения
		 */
		?>
        <a class="btn btn-default" href="<?= $model->fullFname?>" target="_blank">
            <span class="far fa-eye"></span>
            Открыть
        </a>

        <br />

        <br />Предпросмотр:<br />
        <img src="<?= $model->viewThumb ?>" />

	<?php }
} ?>