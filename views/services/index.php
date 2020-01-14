<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\Services[] */

$this->title = \app\models\Services::$title;
$this->params['breadcrumbs'][] = $this->title;
$models=$dataProvider->models;

$renderer=$this;
?>
<div class="services-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Новый сервис', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
	
	        [
		        'attribute'=>'name',
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/services/item',['model'=>$data]);
		        }
	        ],
            //'name',
            //'description:ntext',
            //'is_end_user',
	        'sla_id',
            'userGroup.name',
            //'notebook:ntext',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>

<?php
$links=[];
foreach ($models as $service) {
	foreach ($service->depends as $parent) {
		$links[]=[
			'source'=>$parent->name,
			'target'=>$service->name,
			'thref'=>\yii\helpers\Url::to(['view','id'=>$service->id]),
			'shref'=>\yii\helpers\Url::to(['view','id'=>$parent->id]),
			'stype'=>$parent->is_end_user?'user':'tech',
			'ttype'=>$service->is_end_user?'user':'tech',
		];
	}
	foreach ($service->comps as $server) {
		$links[]=[
			'source'=>$server->name,
			'target'=>$service->name,
			'thref'=>\yii\helpers\Url::to(['view','id'=>$service->id]),
			'shref'=>\yii\helpers\Url::to(['/comps/view','id'=>$server->id]),
			'stype'=>'server',
			'ttype'=>$service->is_end_user?'user':'tech',
		];
	}
}
if (count($links)) {
//http://bl.ocks.org/mbostock/1153292
?>
	<h2>Карта зависимостей</h2>
	<style>

		.link {
			fill: none;
			stroke: #666;
			stroke-width: 1px;
		}

		circle.user {
			fill: cyan;
			stroke: #333;
			stroke-width: 1px;
		}

		circle.server {
			fill: #ccc;
			stroke: #333;
			stroke-width: 1px;
		}

		circle.tech {
			fill: yellow;
			stroke: #333;
			stroke-width: 1px;
		}

		text.d3js {
			font: 10px sans-serif;
			text-shadow: 0 1px 0 #fff, 1px 0 0 #fff, 0 -1px 0 #fff, -1px 0 0 #fff;
			cursor: pointer;
		}

	</style>
<script src="//d3js.org/d3.v3.min.js"></script>
<script>
	var links=<?= json_encode($links, JSON_UNESCAPED_UNICODE) ?>;
    var nodes = {};

    // Compute the distinct nodes from the links.
    links.forEach(function(link) {
        link.source = nodes[link.source] || (nodes[link.source] = {name: link.source, type:link.stype, href:link.shref});
        link.target = nodes[link.target] || (nodes[link.target] = {name: link.target, type:link.ttype, href:link.thref});
    });

    var width = 960,
        height = 700;

    var force = d3.layout.force()
        .nodes(d3.values(nodes))
        .links(links)
        .size([width, height])
        .linkDistance(90)
        .charge(-400)
        .on("tick", tick)
        .start();

    var svg = d3.select("body").append("svg")
        .attr("width", width)
        .attr("height", height)
        .attr("xmlns","http://www.w3.org/2000/svg")
    	.attr("xmlns:xlink","http://www.w3.org/1999/xlink");

    // Per-type markers, as they don't inherit styles.
    svg.append("defs").selectAll("marker")
        .data(["depend"])
        .enter().append("marker")
        .attr("id", function(d) { return d; })
        .attr("viewBox", "0 -5 10 10")
        .attr("refX", 15)
        .attr("refY", -1.5)
        .attr("markerWidth", 6)
        .attr("markerHeight", 6)
        .attr("orient", "auto")
        .append("path")
        .attr("d", "M0,-5L10,0L0,5");

    var path = svg.append("g").selectAll("path")
        .data(force.links())
        .enter()
		.append("path")
        .attr("class", function(d) { return "link"})
        .attr("marker-end", function(d) { return "url(#depend)"; });

    var circle = svg.append("g").selectAll("circle")
        .data(force.nodes())
        .enter().append("circle")
        .attr("r", 6)
        .attr("class", function(d) { return d.type; })
        .call(force.drag);

    var text = svg.append("g").selectAll("text")
        .data(force.nodes())
        .enter()
			//.append("a")
	        //.attr("xlink:href", function(d) { return d.href; })
		.append("text")
	        .attr("class", "d3js")
			.attr("x", 8)
        	.attr("y", ".31em")
			.text(function(d) { return d.name; })
    		.on("click", function(d){
    		    window.open(d.href,'_self')
	});

    // Use elliptical arc path segments to doubly-encode directionality.
    function tick() {
        path.attr("d", linkArc);
        circle.attr("transform", transform);
        text.attr("transform", transform);
    }

    function linkArc(d) {
        var dx = d.target.x - d.source.x,
            dy = d.target.y - d.source.y,
            dr = Math.sqrt(dx * dx + dy * dy);
        return "M" + d.source.x + "," + d.source.y + "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y;
    }

    function transform(d) {
        return "translate(" + d.x + "," + d.y + ")";
    }

</script>

<?php }
