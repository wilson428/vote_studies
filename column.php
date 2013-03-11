<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width">

        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->

        <link rel="stylesheet" href="src/css/normalize.css">
        <link rel="stylesheet" href="src/css/main.css">
        <link rel="stylesheet" href="src/css/template.css">
        <link rel="stylesheet" href="src/css/chosen.css">
        <link href='http://fonts.googleapis.com/css?family=Arvo' rel='stylesheet' type='text/css'>   
        <link href='http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css' rel='stylesheet' type='text/css'>   
        <script src="src/js/vendor/modernizr-2.6.2.min.js"></script>
        <style>
.node {
  stroke: #fff;
  stroke-width: 1px;
}

.link {
    stroke: #999;
    stroke-opacity: 0.7;
    stroke-width: 0.5;
}

.slider {
    width: 300px;
    height: 5px;
    margin: 0 3px 0 0;
}

.tip {
    background: #FFF;
    border: 1px solid gray;
    padding: 7px;
    font-size: 12px;
}
        </style>        
    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
        <![endif]-->

        <!-- Add your site or application content here -->
        <div class="main">
            <img class="logo" src="src/img/logo.png" />
            <div class="title">Vote Patterns</div>
            <div class="intro"></div>
            <div class="control">
                <div class="left">
                    <div class="label">Connect <span id="members">senators</span> who voted together at least <span id="thresh">75%</span> of the time</div>
                    <div class="slider" id="slider"></div>
                </div>        
                <div class="right">
                    <div class="subhead">SESSION</div>
                    <select id="sessions" data-placeholder="Choose a session..." style="width:180px;" class="chzn-select">
                        <option value=""></option>
                    </select>
                </div>
            </div>
            <div class="funstuff">
                <div class="left">
                	<div class="canvas" id="canvas" style="width:400px;float:left"></div>
                </div>
                <div class="right">
                    <div class="subhead">LAWMAKER</div>
                    <select id="roster" data-placeholder="Choose a senator..." style="width:180px;" class="chzn-select">
                        <option value=""></option>
                    </select>
                    <div class="kiosk" id="kiosk">
                        <div class="head"></div>            
                        <div class="details"></div>            
                        <img class="pic" />    
                        <div class="databox"></div>                
                        <div class="bff"></div>                
                    </div>
                </div>
            </div>
            <div class="notes">[Notes]</div>
        	<div class="btn"><a id="data" href="" target="_blank">DATA</a></div>    
    		<div class="btn"><a id="source" href="" target="_blank">SOURCE</a></div>
            <div class="btn" id="embed">EMBED</div>    
            <div style="clear:both"></div>
        </div>

        <div class="embedbox" id="embedbox">
            <img id="close" src="http://4b067982625541b415aa-69589cd039d332357e4543fc28818765.r17.cf1.rackcdn.com/close.gif" /><br />
		    <textarea id="embedcode" style="margin-left: 15px; border: none; width:350px; height: 65px"></textarea>		
    	</div>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/jquery-ui.min.js"></script>
        <script>window.jQuery || document.write('<script src="src/js/vendor/jquery-1.8.3.min.js"><\/script>')</script>
        <script src="src/js/plugins.js"></script>
        <script src="src/js/main.js"></script>
        <script src="src/js/vendor/chosen.jquery.js"></script>
        <script src="src/js/vendor/d3.v3.js"></script>
        <script src="src/js/init.js"></script>
        <script>
/*global d3 tooltip*/


var width = 380,
    height = 380,
    margin = 50,
    sessions = [110, 113],
    session = 113,
    floor = 12,
    start = 75;
    
for (var s=sessions[0]; s <= sessions[1]; s += 1) {
    $("<option />", {
        value: s,
        html: s
    }).appendTo("#sessions");
}

load(113);

function load(session) {
    d3.select("#sessions > option[value='113']").attr("selected", "true");
    $.when($.get("data/output/senate/" + session + "/crossvote.json"), $.get("data/output/senate/" + session + "/phonebook.json")).then(function(d1, d2) {
        make(d1[0], d2[0]);
        //init(JSON.parse(d1[0]), JSON.parse(d2[0]));
    });
}

function make(crossvote, phonebook) {
    $('#slider').slider("value", start);
    $('#thresh').html(start + "%");

    var members = d3.keys(phonebook),
        nodes = {},
        links = {},
        every_link = {},
        besties = {},
        nodebook;
    

    // make list of nodes
    for (var c = 0; c < members.length; c += 1) {
        if (phonebook[members[c]].votes >= floor) {
            nodes[members[c]] = { 
                id: members[c],
                name: phonebook[members[c]].name,
                votes: phonebook[members[c]].votes,
                bioguide: phonebook[members[c]].bioguide,
                info: phonebook[members[c]].name.match(/([A-z]+)\. (.+?) \[([A-Z]+)-([A-Z]+)/) 
            };

            nodes[members[c]].realname = nodes[members[c]].info[2] + " (" + nodes[members[c]].info[3] + "-" + nodes[members[c]].info[4] + ")";
            
            $("<option />", {
                value: nodes[members[c]].id,
                html: nodes[members[c]].realname
            }).appendTo("#roster");            
        }
    }

    $("#roster").chosen();
    //$("#sessions").chosen();

    $.each(crossvote, function(i, v) {
        $.each(v, function(ii, vv) {
            if (vv[1] >= floor) {
                every_link[i + "_" + ii] = {
                    source: nodes[i],
                    target: nodes[ii], 
                    common: vv[0],
                    total: vv[1],
                    rate: vv[1] > 1 ? vv[0] / vv[1] : 0
                };
            }
        });
    });
    
    //optional party filter
    
    
    //get besties
    var besties = {}
    $.each(every_link, function(i, v) {        
        var edges = i.split("_");
        if (!besties.hasOwnProperty(edges[0])) {
            besties[edges[0]] = [0, []];
        }
        if (!besties.hasOwnProperty(edges[1])) {
            besties[edges[1]] = [0, []];
        }
        if (v.rate > besties[edges[0]][0]) {
            besties[edges[0]][0] = v.rate;
            besties[edges[0]][1] = [edges[1]];
        } else if (v.rate === besties[edges[0]][0]) {
            besties[edges[0]][1].push([edges[1]]);            
        }
        
        if (v.rate > besties[edges[1]][0]) {
            besties[edges[1]][0] = v.rate;
            besties[edges[1]][1] = [edges[0]];
        } else if (v.rate === besties[edges[1]][0]) {
            besties[edges[1]][1].push([edges[0]]);            
        }
    });    
        
    //every_link now has all possible connections    
    links = filtered(start / 100, floor);
    
    //console.log(besties);
    
    function filtered(threshold, floor) {
        var g = [];
        
        //clear node references
        nodebook = {};
        
        $.each(every_link, function(i, v) {
            if (!g.hasOwnProperty(i) && v.total >= floor && v.rate >= threshold) {
                g[i] = v;
                //add to dictionary of links per nodes
                var edges = i.split("_");
                if (!nodebook.hasOwnProperty(edges[0])) {
                    nodebook[edges[0]] = [];
                }
                if (!nodebook.hasOwnProperty(edges[1])) {
                    nodebook[edges[1]] = [];
                }
                nodebook[edges[0]].push(edges[1]); 
                nodebook[edges[1]].push(edges[0]);
            }
        });
        return g;
    }

    var force = d3.layout.force()
        .nodes(d3.values(nodes))
        .links(d3.values(links))
        //.nodes(d3.values(nodes).filter(function(d) { return d.info[3] === 'R' }))
        //.links(d3.values(links).filter(function(d) { return d.target.info[3] === 'R' && d.source.info[3] === 'R' }))
        .size([width - 2 * margin, height - 2 * margin])
        .charge(-150)
        .gravity(0.4)
        .on("tick", tick)
        .start();

    var svg = d3.select("#canvas").append("svg")
        .attr("width", width)
        .attr("height", height);

    var network = svg.append("g").attr("transform", "translate(50,50)");

    var link = network.selectAll(".link")
        .data(force.links())
        .enter().append("line")
        .attr("class", "link");
        
    var node = network.selectAll(".node")
        .data(force.nodes())
        .enter().append("circle")
        .attr("class", "node")
        .attr("id", function(d) { return "node-" + d.id; })
        .attr("r", 5)
        //.style("opacity", function(d) { return d.weight > 0 ? 1 : 0; })
        .on("mouseover", function(d) {
            //d3.select(this).style("fill", "orange");
            highlight(d.id);
            tooltip.html(d.info[1] + ". " + d.realname + "<br /><em>Click to highlight</em>");
            return tooltip.style("visibility", "visible");
        })
        .on("mousemove", function() { return tooltip.style("top", (event.pageY - 10)+"px").style("left",(event.pageX + 10)+"px");})
        .on("mouseout", function() { 
            //d3.selectAll(".node").style("fill", function(d) { return !d.info ? "#CCC" : (d.info[3] === 'R' ? "#900" : "#009"); });        
            d3.selectAll(".node").style({
                'stroke-width': 1,
                stroke: '#FFF'
            });
            d3.selectAll(".node").style("fill", function(d) { return !d.info ? "#CCC" : (d.info[3] === 'R' ? "#900" : "#009"); });                    
            return tooltip.style("visibility", "hidden");
        })
        .on("click", function (d) {
            select(d.id);
            
        })
        .style("fill", function(d) { return !d.info ? "#CCC" : (d.info[3] === 'R' ? "#900" : "#009"); });
        
    function tick () {
      node.attr("cx", function(d) { return d.x; })
          .attr("cy", function(d) { return d.y; });
    
      link.attr("x1", function(d) { return d.source.x; })
          .attr("y1", function(d) { return d.source.y; })
          .attr("x2", function(d) { return d.target.x; })
          .attr("y2", function(d) { return d.target.y; });
    }
    
    $('#slider').bind("slide", function(evt, ui) {    
        unselect();
        $('#thresh').html(ui.value + "%");
        links = filtered(ui.value / 100, floor);
        force.links(d3.values(links));        
        
        link = network.selectAll(".link")
            .data(force.links());

        node = network.selectAll(".node")
            .data(force.nodes());

        link.enter()
            .insert("line", ".node")        
            .attr("class", function(d) { return "link" });
            
        link.exit()
            .attr("class", function(d) { return "chris"; })
            .remove();
            
        force.start();
    });
    
    $('#roster').bind("change", function() {        
        highlight($(this).val());
        select($(this).val());
        //d3.selectAll(".node").style("fill", function(d) { return !d.info ? "#CCC" : (d.info[3] === 'R' ? "#900" : "#009"); });        
        //d3.select('#node-' + $(this).val()).style("fill", "orange");        
    });
    
    function select (mid) {   
        //unselect();
        //fill in info box        
        $('#kiosk > .head').html(nodes[mid].info[2]);
        $('#kiosk > .details').html(nodes[mid].info[3] + "-" + nodes[mid].info[4]);
        $('#kiosk > .pic').attr("src", "//bioguide.congress.gov/bioguide/photo/" + nodes[mid].bioguide[0] + "/" + nodes[mid].bioguide + ".jpg" );
        $('#kiosk > .pic').show();        
        if (nodebook[mid]) {
            $('#kiosk > .databox').html(
                "<div class='subhead'>Connections</div>" + 
                "Voted with " + nodebook[mid].length + " other senators at least " + $('#slider').slider("value") + " percent of the time.<br />"            
            );
        }
        var bffs = "";
        $.each(besties[mid][1], function(i, v) {
            bffs += nodes[v].realname + "<br />";
        });        
        $('#kiosk > .bff').html("<div class='subhead'>BFFs</div>" + bffs);
        
        //highlight(mid);
    }
    
    $(".pic").error(function(e) {
        $('#kiosk > .pic').hide();        
    });
    
    function unselect() {
        d3.selectAll(".node").style({
            'stroke-width': 1,
            stroke: '#FFF'
        });
        d3.selectAll(".node").style("fill", function(d) { return !d.info ? "#CCC" : (d.info[3] === 'R' ? "#900" : "#009"); });                        
        //$('#kiosk > div').empty();
    }
    
    function highlight (mid) {    
        unselect();
        d3.select('#node-' + mid).style("fill", "orange");        
        if (nodebook[mid]) {
            var neighbors = 
                d3.selectAll(".node").filter(function(e) { 
                    return nodebook[mid].indexOf(e.id) != -1; 
                });
            neighbors.style({
                stroke: "#FFFF99",
                'stroke-width': 3
            });        
        }
    }
}

        </script>
    </body>
</html>
