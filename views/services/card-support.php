<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;
?>
		<h4>
			Ответственный: <?= $this->render('/users/item',['model'=>$model->responsible,'static_view'=>$static_view]) ?>
		</h4>
		<?php if (count($model->support)) { ?>
			<p>
				Поддержка:
				<?php
				$users=[];
				foreach ($model->support as $user)
					$users[]=$this->render('/users/item',['model'=>$user,'static_view'=>$static_view]);
				echo implode(", ",$users);
				?>
			</p>
			<br />
		<?php }

