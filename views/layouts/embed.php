<?php
use app\assets\AppAsset;
use yii\helpers\Html;
use yii\web\View;
/** @var View $this */
/** @var string $content */
AppAsset::register($this);
$this->beginPage();
?>

<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body>

	<?php $this->beginBody() ?>

	<div class="container container-large">

		<?= $content ?>

	</div>

	<?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
