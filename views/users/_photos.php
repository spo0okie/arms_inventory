<?php

use app\models\Scans;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

/**
 * Фото сотрудника в стиле мессенджера: аватар — последнее по дате изображение,
 * по клику открывается модалка-галерея с прокруткой по всем фото (последнее — первым).
 * Портрет для выгрузки в Bitrix — это же последнее изображение ($model->photo).
 */

if (!isset($static_view)) $static_view = false;

$photos = $model->photos;
$modalId = 'user-photos-modal-' . $model->id;
$carouselId = $modalId . '-carousel';
?>
<div class="user-photos d-flex flex-column align-items-center flex-shrink-0">

	<div class="position-relative flex-shrink-0" style="width:128px;height:128px;">
		<?php if (count($photos)) { ?>
			<a href="#" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>"
			   class="user-photo-avatar" title="Фотографии сотрудника (<?= count($photos) ?>)">
				<?= Html::img($photos[0]->thumbUrl, [
					'class' => 'img-thumbnail rounded-circle',
					'style' => 'width:128px;height:128px;object-fit:cover;',
					'alt' => Html::encode($model->Ename),
				]) ?>
			</a>
		<?php } else { ?>
			<span class="text-muted d-block" style="width:128px;height:128px;" title="Фотографий нет">
				<i class="fas fa-user-circle fa-9x opacity-25 w-100"></i>
			</span>
		<?php } ?>
		<?php if (!$static_view) { ?>
			<?= Html::a('<i class="fas fa-pencil-alt"></i>', ['uploads', 'id' => $model->id], [
				'class' => 'position-absolute',
				'style' => 'bottom:4px;right:4px;padding:0;',
				'qtip_ttip' => 'Загрузить/изменить фотографии сотрудника',
			]) ?>
		<?php } ?>
	</div>

</div>

<?php if (count($photos) && !$static_view) { ?>
	<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?= Html::encode($model->Ename) ?> - фотографии</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
				</div>
				<div class="modal-body p-0">
					<div id="<?= $carouselId ?>" class="carousel slide bg-dark" data-bs-ride="false">
						<div class="carousel-inner">
							<?php foreach ($photos as $i => $scan) { ?>
								<div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
									<?= Html::img($scan->fileExists ? $scan->fullFname : Scans::noThumb(), [
										'class' => 'd-block w-100',
										'style' => 'max-height:70vh;object-fit:contain;',
										'alt' => Html::encode($scan->name),
									]) ?>
								</div>
							<?php } ?>
						</div>
						<?php if (count($photos) > 1) { ?>
							<button class="carousel-control-prev" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="prev">
								<span class="carousel-control-prev-icon" aria-hidden="true"></span>
								<span class="visually-hidden">Предыдущее</span>
							</button>
							<button class="carousel-control-next" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="next">
								<span class="carousel-control-next-icon" aria-hidden="true"></span>
								<span class="visually-hidden">Следующее</span>
							</button>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
