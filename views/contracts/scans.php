<?php
/**
 * Кусочек кода который генерит плиточки сканов документа
 */

/* @var $this yii\web\View */
/* @var $model \app\models\Contracts */
/* @var $upload_enabled boolean */
if (!isset($static_view)) $static_view=false;
?>
<p>
    <?php
    if (is_array($scans=$model->scans)&&count($scans)) foreach ($scans as $scan)
        echo $static_view?
            $this->render('/scans/thumb',['model'=>$scan,'contracts_id'=>$model->id,'static_view'=>$static_view])
        :
            $this->render('/scans/preview',[
                'model'=>$scan,
                'contracts_id'=>$model->id,
                'static_view'=>$static_view,
                'show_preview_max_size'=>640*1024
            ]);
    ?>
</p>

