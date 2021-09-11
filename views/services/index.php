<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\Services[] */

\yii\helpers\Url::remember();
$this->title = \app\models\Services::$title;
$this->params['breadcrumbs'][] = $this->title;
$models=$dataProvider->models;

$showChildren=Yii::$app->request->get('showChildren',false);
$showArchived=Yii::$app->request->get('showArchived',false);

$childrenLabel=$showChildren?'Скрыть дочерние сервисы':'Показать дочерние сервисы';
$childrenUrl=array_merge(['index'],Yii::$app->request->get());
$childrenUrl['showChildren']=!$showChildren;

$archivedLabel=$showArchived?'Скрыть архивные':'Показать архивные';
$archivedUrl=array_merge(['index'],Yii::$app->request->get());
$archivedUrl['showArchived']=!$showArchived;

$renderer=$this;
?>
<div class="services-index">

    <h1><?= Html::encode($this->title) ?></h1>

	<?= Html::a('Новый сервис', ['create'], ['class' => 'btn btn-success']) ?>
	<div class="pull-right">
		<?= Html::a(
			$childrenLabel,
			$childrenUrl
		) ?>
		//
		<?= Html::a(
			$archivedLabel,
			$archivedUrl
		) ?>
		
	</div>
	
	<?php Pjax::begin(); ?>
	<?= $this->render('table',compact('dataProvider','searchModel'))?>
</div>

<?php
/*
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
*/
Pjax::end();

//if (count($links)) {
if (false) {
//http://bl.ocks.org/mbostock/1153292
?>
	<h2>Карта зависимостей</h2>
	<style>

		.link {
			fill: none;
			stroke: #666;
			stroke-width: 1px;
		}

		.shaded {opacity: 0.2;}
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
		
		circle.highlighted {
			stroke: lime !important;
			stroke-width: 3px !important;
		}

		text.d3js {
			font: 10px sans-serif;
			text-shadow: 0 1px 0 #fff, 1px 0 0 #fff, 0 -1px 0 #fff, -1px 0 0 #fff;
			cursor: pointer;
		}

	</style>
<script src="//d3js.org/d3.v3.min.js"></script>
<script>
	let links=<?= json_encode($links, JSON_UNESCAPED_UNICODE) ?>;
    let nodes = {};
    let idx=0;

    //это почти оригинальный код, но я не жабаскриптер, читал его тяжело
    //link.source = nodes[link.source] || (nodes[link.source] = {name: link.source, type:link.stype, href:link.shref});
    //link.target = nodes[link.target] || (nodes[link.target] = {name: link.target, type:link.ttype, href:link.thref});

    //перебираем Линки
    links.forEach(function(link) {
        //сорс и таркет вместо строк делаем объектами
		//если сорс-нода создана
		if (link.source in nodes) {
            link.source = nodes[link.source];
		} else {
            nodes[link.source] = {
                id:	idx++,			//id
                name: link.source,	//источник
				type:link.stype, 	//тип источника
				href:link.shref,	//ссылка источника
                sources: [],		//источники
                targets: [],		//зависимые
            };
            link.source = nodes[link.source];
		}

        //если сорс-нода создана
        if (link.target in nodes) {
            link.target = nodes[link.target];
        } else {
            nodes[link.target] = {
                id:	idx++,			//id
                name: link.target,	//получатель (зависисимый)
                type:link.ttype, 	//тип зависимого
                href:link.thref,	//ссылка зависимого
                sources: [],		//источники
                targets: [],		//зависимые
            };
            link.target = nodes[link.target];
        }

        //console.log(link.source);
        //console.log(nodes[link.source]);
        //тут поперла самодеятельность. надо обозначит предков и потомков
		if (! (link.target.id in link.source.targets)) {
		    link.source.targets.push(link.target.id);
        }
        //if (! (link.source in nodes[link.target].sources)) nodes[link.target].sources[link.source]=nodes[link.source];
    });

    var width = 960,
        height = 700;

    var force = d3.layout.force()
        .nodes(d3.values(nodes))
        .links(links)
        .size([width, height])
        .linkDistance(90)
        .charge(-300)
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

    //связки
    var path = svg.append("g").selectAll("path")
        .data(force.links())
        .enter()
		.append("path")
        .attr("class", function(d) {
            return "link source_"+d.source.id+" target_"+d.target.id;
        })
        .attr("marker-end", function(d) { return "url(#depend)"; });

    var circle = svg.append("g").selectAll("circle")
        .data(force.nodes())
        .enter().append("circle")
        .attr("r", 4)
        .attr("class", function(d) {
            let circle_targets=[];
            //console.log(d.targets);
            d.targets.forEach(function(item) {
                circle_targets.push('targets_to_n'+item);
			});
            //console.log(circle_targets);
            return d.type+" circle circle_"+d.id+" "+circle_targets.join(' ');
        })
		.attr("node_id",function (d) {
			return d.id;
        })
		.on('mouseover',function(d){
		    $('.link,.text,.circle').addClass('shaded');
            highlightObject(d.id)
		})
        .on('mouseout',function(d){
            $('.link,.text,.circle').removeClass('shaded');
        })
        .call(force.drag);
    

    var text = svg.append("g").selectAll("text")
        .data(force.nodes())
        .enter()
		.append("text")
	        .attr("class", function(d) {
	            return "d3js text describes_"+d.id
            })
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
        return "M" + d.source.x + "," + d.source.y + " L" + d.target.x + "," + d.target.y;
    }

    function transform(d) {
        return "translate(" + d.x + "," + d.y + ")";
    }

    function highlightObject(id) {
        $('.circle_'+id+', .target_'+id+', .describes_'+id).removeClass('shaded');
        $('.targets_to_n'+id).each(function () {
            $(this).removeClass('shaded');
            highlightObject($(this).attr('node_id'));
        })
    }

</script>

<?php }

$this->registerJs('$(document).on("pjax:complete", function(event) {
			input=$("input[type=\'text\']:visible:first");
			inputVal=input.val();
			input.val("").focus().val(inputVal);
		});');
