<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Services::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="services-view">

    <?= $this->render('card',['model'=>$model]) ?>
	
</div>
<div class="wiki-render-area">
	<?php if (!empty(Yii::$app->params['wikiUrl']) && !empty(Yii::$app->params['wikiUser']) && !empty(Yii::$app->params['wikiPass'])) {
		$items=explode("\n",$model->links);
		foreach ($items as $item) {
			$item=trim($item);
			if (!strlen($item)) continue;
			$tokens=explode(' ',$item);
			$url=$tokens[count($tokens)-1];
			
			if (strpos($url,Yii::$app->params['wikiUrl'])===0) {
				$arrContextOptions=[
						"http" => [
							"header" => "Authorization: Basic ".base64_encode(Yii::$app->params['wikiUser'].":".Yii::$app->params['wikiPass'])
						],
						"ssl"=>[
							"verify_peer"=>false,
							"verify_peer_name"=>false,
						],
					];
				$page=file_get_contents($url,false,stream_context_create($arrContextOptions));
				$startCode='<div class="dw-content">';
				$endCode='<div class="comment_wrapper" id="comment_wrapper">';
				if ($startPos=strpos($page,$startCode)) {
					$page=substr($page,$startPos+strlen($startCode));
					if ($endPos=strpos($page,$endCode)) {
						$page=substr($page,0,$endPos);
						if ($titlePos=strpos($page,'</h1>')) {
							$page=substr($page,$titlePos+5);
						}
						echo '<h1>Wiki:</h1>';
						echo $page;
					} else echo "no end";
				} else echo "no start";
				
			}
		}
	}?>
</div>