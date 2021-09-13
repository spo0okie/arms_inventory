<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

?>
<div class="schedules-acls">
	<h2>Доступы</h2>
	<table class="acls-table">
		<tr>
			<th>
				Ресурс
			</th>
			<th>
				Тип доступа
			</th>
			<th>
				Субъект
			</th>
		</tr>
		<?php foreach ($model->acls as $acl) { ?>
			<?= $this->render('/acls/tdrow',['model'=>$acl]) ?><br/>
		<?php } ?>
		
	</table>
	<?= Html::a('Добавить',['acls/create','schedules_id'=>$model->id],['class'=>'btn btn-success'])?>
</div>
