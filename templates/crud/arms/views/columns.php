<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

$tableSchema = $generator->getTableSchema();

echo "<?php\n";
?>

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */


use yii\helpers\Html;


if(!isset($static_view))$static_view=false;
$renderer = $this;

return [
<?php

	foreach ($tableSchema->columns as $column) {
		$format = $generator->generateColumnFormat($column);
		echo "\t'{$column->name}'";
		if (substr($column->name,strlen($column->name)-4)=='_id') {
			$relation=substr($column->name,0,strlen($column->name)-4);
			$relationView=Inflector::camel2id($relation);
			echo "=>[\n";
			echo "		'value'=>function(\$data) use (\$renderer){\n";
			echo "			return \$renderer->render('$relationView/item',['model'=>\$data->$relation]);\n";
			echo "		},\n";
			echo "	]";
		} else {
			switch ($column->name) {
				case $nameAttribute:
					echo "=>[\n";
					echo "		'value'=>function(\$data) use (\$renderer){\n";
					echo "			return \$renderer->render('item',['model'=>\$data]);\n";
					echo "		},\n";
					echo "	]";
					break;
				case 'comment';
				case 'description';
				case 'notepad';
				case 'history';
					echo "=>['format' =>'text']";
					break;
				default:
					echo " /* ??? */";
			}
			echo ",\n";
		}
	}
?>
		
];