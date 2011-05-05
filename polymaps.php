<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Republic Of Letters Network Visualization</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="js/jquery/ui/css/smoothness/jquery-ui-1.8.5.custom.css"/>
<link rel="stylesheet" type="text/css" href="main-new.css"/>
<script type="text/javascript" src="js/json-minified.js"></script>
<script type="text/javascript" src="js/polymaps/polymaps.min.js"></script>
<script type="text/javascript" src="js/protovis-3.2/protovis-r3.2.js"></script>
<script type="text/javascript" src="js/jquery/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/jquery/ui/js/jquery-ui-1.8.5.custom.min.js"></script>
<script type="text/javascript">
    var map = null;
	var mapCanvas = null;
	var topLeft;
	var botRight;

	function initPO()
	{
		po = org.polymaps;
		map = po.map()
		    .container(document.getElementById("mainPanel").appendChild(po.svg("svg")).appendChild(po.svg('g')))
		    .center({lat: 40, lon: -30})
		    .zoom(4)
		    .zoomRange([3, 10])
		    .add(po.interact());
	
		map.add(po.image()
		    .url(po.url("http://{S}tile.cloudmade.com"
		    + "/79c430376d494b3eb2de8b853e6d9765" // http://cloudmade.com/register
		    + "/20760/256/{Z}/{X}/{Y}.png")
		    .hosts(["a.", "b.", "c.", ""])));

	    pvSetup();
	}
	
	function initVis()
	{
		visLayer = po.layer(updateMainPanel).tile(false);
		map.add(visLayer);
	}
</script>
<script type="text/javascript">
	///POLYMAPS
	var po;
	var map;
	var _tile;
	var _projection;
	
	var letters;
	var people;
	var locations;
	var sources;
	var voltaire = [50510];
	var locke = [47659];
	var franklin = [38468, 40534, 48765, 285969];
	var kircher = [46727];
	var vallisneri = [283232];
	var importantPeople = {'voltaire':[0, 0], 'locke':[0, 0], 'franklin':[0, 0], 'kircher':[0, 0], 'vallisneri':[0, 0]};
	
	var lettersByYear = {};
	var incompleteLettersByYear = {};
	var lettersWithoutDates = [];
	var lettersByAuthor = {};
	var lettersByRecipient = {};
	var lettersBySrcLoc = {};
	var lettersByDstLoc = {};
	var lettersBySource = {};
	var letterCorrespondents = {};
	var correspondentStats = {};
	
	var visibleLocations = {};

	var letterSources = {};
	var letterAuthors = {};
	var letterRecipients = {};
	var letterUrls = {};

	var filteredSources = {};
	
	var links;
	var volumes;
	var incompletes;

	var linesOrig = [];
	var lines = [];
	var persistLines = [];
	
	var dotsOrig = [];
	var dots = [];
	var seenLocs = {};


	// Range Limits
	var minYear;
	var maxYear;
	var maxDocs;
	var maxPlottableDocs;

	// Defaults
	var startYear = 1730;
	var endYear = 1760;
	
	var startYearSpan;
	var endYearSpan;

	// Line size limits
	var minLineWidth = 1.0;
	var maxLineWidth = 30.0;

	// UI dimensions
	var minWidth = 600;
	var minHeight = 400;
	var padding = 10;
	var width;
	var height;
	var mainPanelWidth;
	var mainPanelHeight;
	var timelineWidth;
	var timelineHeight = 200;
	var statsPanelHeight = 40;
	var sidePanelWidth;
	var sidePanelHeight;

	// Protovis objects
	var mainPanel;
	var timeline;
	var timelineBorder;
	var sidePanel;
	var colorScale;
	var visReady = false;
	var mapReady = false;
	var timelineReady = false;

	function resize()
	{
		width = $(window).width();
		height = $(window).height();
		$('#mainPanel').height((height - 2 * padding) - (timelineHeight + padding) - (statsPanelHeight + padding));
	}
	window.onload = resize();
</script>
</head>
<body onload="initPO()">
<div id="screenTransparency"></div>
<div id="detailPanel"></div>
<div id="mainConainer" align="center">
	<div id="controlPanel">
		<div class="transparency"></div>
		<div class="pulloutTab"></div>
		<div class="content">
			<div class="filters">
				<?php
					$filters = array('Locations', 'People', 'Sources');
				?>
				<?php foreach($filters as $filter): ?>
				<h3><?php echo $filter; ?></h3>
				<div>dfd</div>
				<?php endforeach; ?>
			</div>
		</div>
		<script type="text/javascript">
			////// ACCORDION FOR FILTERS
			$("#controlPanel .content .filters").accordion({ header: "h3", fillSpace: true, collapsible: true, active: false });
		
			////// CPANEL ANIMATIONS
			var cpanel_out = false;
			$("#controlPanel .pulloutTab").mouseenter(function() {
				if(cpanel_out) return;
				$("#controlPanel .pulloutTab").stop();
				$("#controlPanel .pulloutTab").animate({left: '-49'}, 100);
			});
			
			$("#controlPanel .pulloutTab").mouseleave(function() {
				if(cpanel_out) return;
				$("#controlPanel .pulloutTab").stop();
				$("#controlPanel .pulloutTab").animate({left: '-39'}, 100);
			});
			
			function slideIn()
			{
				$("#controlPanel").stop();
				$("#controlPanel").animate({right: '-302'}, 400);

				$("#controlPanel .pulloutTab").click(function() {
					cpanel_out = true;
					slideOut();
				});

				$("#controlPanel .pulloutTab").css('background-image', 'url("left_icon_small.png")');
			}
			
			function slideOut()
			{
				$("#controlPanel").stop();
				$("#controlPanel").animate({right: '0'}, 400);
				
				$("#controlPanel .pulloutTab").click(function() {
					cpanel_out = false;
					slideIn();
				});

				$("#controlPanel .pulloutTab").css('background-image', 'url("right_icon_small.png")');
			}

			slideIn();
		</script>
	</div>
	<div id="mainPanelContainer"><div id="mainPanel" align="center" style="text-align: center;"></div></div>
	<div id="timelineContainer">
		<div id="timelineContainerTransparency"></div>
		<div id="timelinePanel" align="center" style="text-align: center;"></div>
	</div>
	<div id="statsPanel">
		<p id="sourcesStats">
			Sources ---
		</p>
		<p id="correspondenceStats">
			Correspondence (Sent/Received) ---
			Voltaire: <span id="voltaire_stats">0/0</span>
			Locke: <span id="locke_stats">0/0</span>
			Franklin: <span id="franklin_stats">0/0</span>
			Kircher: <span id="kircher_stats">0/0</span>
			Vallisneri: <span id="vallisneri_stats">0/0</span>
		</p>
	</div>
	<script type="text/javascript">
		function resize()
		{
			width = $(window).width();
			height = $(window).height();
			$('#mainPanelContainer').height(height - (statsPanelHeight + padding) - (timelineHeight + padding) - padding);
		}
		resize();
	</script>
	<div id="infoOverlayTranparency"></div>
	<div id="infoOverlay">
		<h2 align="center">Republic Of Letters</h2>
		<div id="period">
			<span id="startYear">1700</span> to <span id="endYear">1750</span>
		</div>
	</div>

	<script type="text/javascript+protovis">
		// setup UI
		setDimensions();

		// get people data
		var peopleXHR = getXHR();
		peopleXHR.open( "GET", "data/get-people.php", true );
		peopleXHR.onreadystatechange = function()
		{
			if(peopleXHR.readyState == 4 && peopleXHR.status == 200)
			{
				var response = jsonParse(peopleXHR.responseText);
				people = response.people;
			}
		};
		peopleXHR.send( null );

		//////////// get letters by author/recipient (for stats panel)
		var authRecLettersXHR = getXHR();
		authRecLettersXHR.open("GET", "data/get-letters-by-person.php", true);
		authRecLettersXHR.onreadystatechange = function()
		{
			if(authRecLettersXHR.readyState == 4 && authRecLettersXHR.status == 200)
			{
				var response = jsonParse(authRecLettersXHR.responseText);
				lettersByAuthor = response.lettersByAuthor;
				lettersByRecipient = response.lettersByRecipient;
				letterCorrespondents = response.letterCorrespondents;
				updateStatsPanel();
			}
		};
		authRecLettersXHR.send(null);

		//////////// get all sources
		var sourcesXHR = getXHR();
		sourcesXHR.open("GET", "data/get-sources.php", true);
		sourcesXHR.onreadystatechange = function()
		{
			if(sourcesXHR.readyState == 4 && sourcesXHR.status == 200)
			{
				var response = jsonParse(sourcesXHR.responseText);
				sources = response.sources;
			}
		};
		sourcesXHR.send(null);

		//////////// get letters by source (for stats panel)
		var letterSourcesXHR = getXHR();
		letterSourcesXHR.open("GET", "data/get-letters-by-source.php", true);
		letterSourcesXHR.onreadystatechange = function()
		{
			if(letterSourcesXHR.readyState == 4 && letterSourcesXHR.status == 200)
			{
				var response = jsonParse(letterSourcesXHR.responseText);
				letterSources = response.letterSources;
			}
		};
		letterSourcesXHR.send(null);

		//////////// get letter URLs
		var letterUrlsXHR = getXHR();
		letterUrlsXHR.open("GET", "data/get-letter-urls.php", true);
		letterUrlsXHR.onreadystatechange = function()
		{
			if(letterUrlsXHR.readyState == 4 && letterUrlsXHR.status == 200)
			{
				var response = jsonParse(letterUrlsXHR.responseText);
				letterUrls = response.letterUrls;
			}
		};
		letterUrlsXHR.send(null);
	
		function pvSetup()
		{
			//////////// get location data
			var locationsXHR = getXHR();
			locationsXHR.open( "GET", "data/get-locations.php", false );
			locationsXHR.send( null );
			var locQueryResponse = jsonParse( locationsXHR.responseText );
			locations = locQueryResponse.locations;
			dotsOrig = locQueryResponse.dots;

			//////////// get letter data
			var lettersXHR = getXHR();
			lettersXHR.open( "GET", "data/get-letters.php", true );
			lettersXHR.onreadystatechange = function()
			{
				if(lettersXHR.readyState == 4 && lettersXHR.status == 200)
				{
					var response       = jsonParse(lettersXHR.responseText);
					letters            = response.letters;
					lettersByYear      = response.lettersByYear;
					lettersWithoutDate = response.lettersWithoutDate;
					maxPlottableDocs   = response.maxDocs;
					linesOrig		   = response.lines;
					persistLines       = response.persistentLines;
					lineEccentricity   = response.lineEccentricity;

				    colorScale = pv.Scale.log().domain(1, maxPlottableDocs).range("#ffbe1e", "#ff1600").nice();
					mapReady = true;
					initVis();
					$("#mainPanel .loadingOverlay").remove();
				}
			};
			lettersXHR.send( null );

			//////////// get letter volume data by year (for timeline)
			var volumesXHR = getXHR();
			volumesXHR.open( "GET", "data/get-letter-volumes.php", true );
			volumesXHR.onreadystatechange = function()
			{
				if(volumesXHR.readyState == 4 && volumesXHR.status == 200)
				{
					var response  = jsonParse(volumesXHR.responseText);
					maxDocs       = response.maxLetters;
					minYear       = response.minYear;
					maxYear       = response.maxYear;
					volumes       = response.volumes;
					incompletes   = response.incompletes;
					setupTimeline();
					$("#timelineContainer .loadingOverlay").remove();
				}
			};
			volumesXHR.send( null );
			
			timeline = new pv.Panel()
			      .canvas( document.getElementById('timelinePanel') )
			      .width( width - 85 )
			      .height( timelineHeight - 10 )
			      .left( 25 )
			      .bottom( 5 )
			      .overflow( 'visible' );
		}

		function setupTimeline()
		{
			/**********Draw timeline*********/
		    var x = pv.Scale.linear(minYear, maxYear).range(0, width - 85);
		    var y = pv.Scale.linear(0, maxDocs).range(0, timelineHeight - 15 - 10 - 10).nice();
		    var h_ticks = [];
		    var ptr = 0;
		    for( var i = minYear; i <= maxYear; ++i )
		    {
		    	if( i % 10 == 0 )
		    	{
		    		h_ticks[ptr++] = i;
		    	}
		    }
		    var v_ticks = [];
		    ptr = 0;
		    for( var i = 1; i <= maxDocs; i *= 10 )
		    {
		    	v_ticks[ptr++] = i;
		    }
		    
		    var ruleColor = pv.color("#66a3d2");
		    ruleColor.opacity = 0.3;
			timeline.add( pv.Rule )
			        .data( h_ticks )
			        .strokeStyle( ruleColor )
			        .left( x )
			        .height( timelineHeight - 15 - 10 )
			        .top( 5 )
			        .anchor( "bottom" )
			        .add( pv.Label )
			        	.top( timelineHeight - 20 )
			        	.textStyle("white");
			
			timeline.add( pv.Rule )
			        .data( y.ticks() )
			        .strokeStyle( ruleColor )
			        .bottom( function(d) y(d) + 15 )
			        .anchor("left")
			        .add(pv.Label)
			        	.textStyle("white")
			        	.top(function(d) timelineHeight - y(d) - 15 - 10);
			
			/*********Populate timeline*********/
			var barWidth = x(maxYear) - x(maxYear - 1) - 1.5;
			var timelineColors = [pv.color("#ffffff").alpha(.2), pv.color("#ff7a00").alpha(.4)];
			var par = -1;
			var ind = -1;
			timeline.def("par", -1)
					.def("ind", -1);
            timeline.add(pv.Layout.Stack)
                    .bottom(15)
                    .layers([incompletes, volumes])
                    .x(function(d) x(this.index + minYear))
                    .y(function(d) d == 0 ? 0 : y(d))
                    .layer.add(pv.Bar)
                    .width(barWidth)
                    .strokeStyle(function(d) d == 0 ? 'none' : timelineColors[this.parent.index].alpha(1))
                    .lineWidth(0.5)
                    .fillStyle(function(d) {return timelineColors[this.parent.index];})

            /*********Draw timeline filter bar********/
            var filterData = {x: x(startYear), dx: x(endYear) - x(startYear)};
            fx = pv.Scale.linear().range(0, height - 85);
			tlFilter = timeline.add(pv.Bar)
						.data([filterData])
					    .top(6)
					    .left(function(d) d.x)
					    .width(function(d) d.dx)
					    .height(timelineHeight-20-6)
			            .lineWidth(1)
			            .strokeStyle(pv.color("#6d87d6").alpha(.5))
					    .fillStyle(pv.color("#6d87d6").alpha(.3))
					    .cursor("move")
					    .event("mousedown", pv.Behavior.drag())
					    .event("drag", function(d){
						    	startYear = x.invert(d.x);
						    	endYear = x.invert(d.x + d.dx);
						    	if(tlResizeR.data().x > tlResizeL.data().x)
						    	{
							    	tlResizeL.data([{x: x(startYear)}]);
							    	tlResizeR.data([{x: x(endYear)}]);
						    	}
						    	else
						    	{
							    	tlResizeL.data([{x: x(endYear)}]);
							    	tlResizeR.data([{x: x(startYear)}]);
						    	}
						    	tlResizeL.render();
						    	tlResizeR.render();
						    	updateVis();
						    })
					    .event("dragend", function(){});

			var resizeLData = {x: x(startYear)};
		    var tlResizeL = timeline.add(pv.Bar)
					.data([resizeLData])
					.def("highlight", false)
		            .top(6)
		            .left(function(d) d.x - 16)
		            .height(timelineHeight-20-6)
		            .width(16)
		            .lineWidth(1)
		            .strokeStyle(pv.color("#6d87d6").alpha(.5))
		            .fillStyle(function(d) this.highlight() ? pv.color("#ffffff").alpha(.3) : pv.color("#ffffff").alpha(.2))
            		.cursor("ew-resize")
		            .event("mouseover", function(){ tlResizeL.highlight(true); tlResizeL.render(); })
		            .event("mouseout", function(){ tlResizeL.highlight(false); tlResizeL.render(); })
            		.event("mousedown", pv.Behavior.drag())
		    		.event("drag", function(d){
			    			d_2 = tlResizeR.data();
			    			var left = Math.min(d.x, d_2.x);
			    			var right = Math.max(d.x, d_2.x);
			    			startYear = x.invert(left);
			    			endYear = x.invert(right);
			    			tlFilter.data([{x: left, dx: right-left}]);
			    			tlFilter.render();
			    			updateVis();
			    		});

    		var resizeRData = {x: x(endYear)};
		    var tlResizeR = timeline.add(pv.Bar)
					.data([resizeRData])
					.def("highlight", false)
		            .top(6)
		            .left(function(d) d.x)
		            .height(timelineHeight-20-6)
		            .width(16)
		            .lineWidth(1)
		            .strokeStyle(pv.color("#6d87d6").alpha(.5))
		            .fillStyle(function(d) this.highlight() ? pv.color("#ffffff").alpha(.3) : pv.color("#ffffff").alpha(.2))
            		.cursor("ew-resize")
		            .event("mouseover", function(){ tlResizeR.highlight(true); tlResizeR.render(); })
		            .event("mouseout", function(){ tlResizeR.highlight(false); tlResizeR.render(); })
            		.event("mousedown", pv.Behavior.drag())
            		.event("mousedown", pv.Behavior.drag())
		    		.event("drag", function(d){
			    			d_2 = tlResizeL.data();
			    			var left = Math.min(d.x, d_2.x);
			    			var right = Math.max(d.x, d_2.x);
			    			startYear = x.invert(left);
			    			endYear = x.invert(right);
			    			tlFilter.data([{x: left, dx: right-left}]);
			    			tlFilter.render();
			    			updateVis();
			    		});
					
			timeline.render();
		}
		
		function pvZoom()
		{
			updateMainPanel(_tile, _projection);
		}

		function updateVis()
		{
			updateYearDisplay();
			filterByYear();
			updateMainPanel(_tile, _projection);
			updateStatsPanel();
		}

		function updateYearDisplay()
		{
			var startYr = Math.round(startYear);
			var endYr = Math.round(endYear);
			startYearSpan = document.getElementById( 'startYear' );
			endYearSpan = document.getElementById( 'endYear' );
			startYearSpan.innerHTML = startYr;
			endYearSpan.innerHTML = endYr;
		}
	
		function filterByYear()
		{
			if(!mapReady) return;
			var startYr = Math.round(startYear);
			var endYr = Math.round(endYear);

			lines = {};
			dots = [];
			seenLocs = {};
			filteredSources = {};
			correspondentStats = {};
			for(var i = startYr; i < endYr; ++i)
			{
				if(!lettersByYear[i]) continue;
				for(var j = 0; j < lettersByYear[i].length; ++j)
				{
					var uid = lettersByYear[i][j];
					var letter = letters[uid];
					var lineId = "l" + letter.srcLoc + "_" + letter.dstLoc;
					
					if(letterSources[uid])
					{
						if(!filteredSources[letterSources[uid].source])
						{
							filteredSources[letterSources[uid].source] = 0;
						}
						++filteredSources[letterSources[uid].source];
					}

					if(letterCorrespondents[uid])
					{
						var authId = letterCorrespondents[uid].author;
						var recId = letterCorrespondents[uid].recipient;

						if(!correspondentStats[authId])
						{
							correspondentStats[authId] = [0, 0];
						}
						++correspondentStats[authId][0];

						if(!correspondentStats[recId])
						{
							correspondentStats[recId] = [0, 0];
						}
						++correspondentStats[recId][1];
						
					}
					
					if(!locations[letter.srcLoc].coords || !locations[letter.dstLoc].coords) continue;

					if(!seenLocs[letter.srcLoc])
					{
						var dot = {coords:{
								lat:dotsOrig[letter.srcLoc].coords[0],
				           		lon: dotsOrig[letter.srcLoc].coords[1]
							},
				            name: dotsOrig[letter.srcLoc].name
						};
						dots.push(dot);
					}

					if(!seenLocs[letter.dstLoc])
					{
						var dot = {coords:{
								lat:dotsOrig[letter.dstLoc].coords[0],
				           		lon: dotsOrig[letter.dstLoc].coords[1]
							},
				            name: dotsOrig[letter.dstLoc].name
						};
						dots.push(dot);
					}
					
					if(!linesOrig[lineId]) continue;

					if(!lines[lineId])
					{
						lines[lineId] = {size: 0,
								         src: letter.srcLoc,
								         dst: letter.dstLoc,
								         letters: []};
					}
					++lines[lineId].size;
					lines[lineId].letters.push(uid);
				}
			}
		}
	
		function updateMainPanel(tile, projection)
		{
			var g;
			if(_tile != tile)
			{
				_tile = tile;
				_projection = projection;
				g = _tile.element = po.svg("g");
			}
			else
			{
				g = _tile.element;
			}
		
			mainPanel = new pv.Panel()
							.canvas(g)
							.width(map.size().x)
							.height(map.size().y)
							.left(0)
							.top(0);

		    var grayLine = pv.color("#333333").alpha(.2);
			for(i in persistLines)
			{
				var line = persistLines[i];
				var src = locations[line.src];
				var dst = locations[line.dst];
				if(!src.coords || !dst.coords) continue;

				if(!seenLocs[line.src])
				{
					seenLocs[line.src] = true;
					var dot = {coords:{
									lat:dotsOrig[line.src].coords[0],
					           		lon: dotsOrig[line.src].coords[1]
								},
					            name: dotsOrig[line.src].name
					};
					dots.push(dot);
				}

				if(!seenLocs[line.dst])
				{
					seenLocs[line.dst] = true;
					var dot = {coords:{
									lat:dotsOrig[line.dst].coords[0],
					           		lon: dotsOrig[line.dst].coords[1]
								},
					            name: dotsOrig[line.dst].name
					};
					dots.push(dot);
				}
				
				var thickness = line.size;
				var data = [];
				data[0] = {
					coords: {
						lat: src.coords[0],
						lon: src.coords[1]
					},
					name: src.name
				};

				data[1] = {
					coords: {
						lat: dst.coords[0],
						lon: dst.coords[1]
					},
					name: dst.name
				};

				thickness = minLineWidth + ( (thickness / maxPlottableDocs) * (maxLineWidth - minLineWidth) );
				var eccentricity = lineEccentricity[i] ? lineEccentricity[i] : 0.5;
		        mainPanel.add(pv.Line)
		           			    .data(data)
								.def("id", i)
								.left(function(d) _projection(_tile).locationPoint(d.coords).x)
								.top(function(d) _projection(_tile).locationPoint(d.coords).y)
		           			    .lineWidth( thickness )
		           			    .interpolate( "polar" )
		           			    .eccentricity(eccentricity)
		           			    .cursor("pointer")
		           			    .title("FROM: " + data[0].name + " TO: " + data[1].name)
		           			    .strokeStyle(grayLine)
								.event("click", function(){ showPersistentLineDetails(this.id()); });
			}
			
				mainPanel.add(pv.Dot)
						.data(dots)
						.left(function(d) _projection(_tile).locationPoint(d.coords).x)
						.top(function(d) _projection(_tile).locationPoint(d.coords).y)
						.size(45)
						.lineWidth(1)
						.strokeStyle("rgba(255, 255, 255, 0.6)")
						.fillStyle("rgba(30, 120, 180, .6)")
						.cursor("pointer")
						.title(function(d) d.name ? d.name : "Unknown");
			
			for( i in lines )
			{
				var link = lines[i];
				var src = locations[link.src];
				var dst = locations[link.dst];
				if(!src.coords || !dst.coords) continue;
				
				var thickness = link.size;
				var data = [];
				data[0] = {
					coords: {
						lat: src.coords[0],
						lon: src.coords[1]
					},
					name: src.name
				};

				data[1] = {
					coords: {
						lat: dst.coords[0],
						lon: dst.coords[1]
					},
					name: dst.name
				};

			    var lineColor = colorScale(thickness).alpha(.4);
				thickness = minLineWidth + ( (thickness / maxPlottableDocs) * (maxLineWidth - minLineWidth) );
				var eccentricity = lineEccentricity[i] ? lineEccentricity[i] : 0.5;
		        mainPanel.add(pv.Line)
		           			    .data(data)
								.def("id", i)
								.left(function(d) _projection(_tile).locationPoint(d.coords).x)
								.top(function(d) _projection(_tile).locationPoint(d.coords).y)
		           			    .lineWidth( thickness )
		           			    .interpolate( "polar" )
		           			    .eccentricity(eccentricity)
		           			    .cursor("pointer")
		           			    .title("FROM: " + data[0].name + " TO: " + data[1].name)
		           			    .strokeStyle(lineColor)
								.event("click", function(){ showLineDetails(this.id()); });
			}

			mainPanel.render();
		}

		function updateStatsPanel()
		{
			var startYr = Math.round(startYear);
			var endYr = Math.round(endYear);

			for(person in importantPeople)
			{
				importantPeople[person] = [0, 0];
			}

//			for(var year = startYr; year < endYr; ++year)
//			{
//				for(person in importantPeople)
//				{
//					var personIds = window[person];
//					for(var i = 0; i < personIds.length; ++i)
//					{
//						if(lettersByAuthor[personIds[i]] && lettersByAuthor[personIds[i]][year])
//						{
//							importantPeople[person][0] += lettersByAuthor[personIds[i]][year].length;
//						}
//						if(lettersByRecipient[personIds[i]] && lettersByRecipient[personIds[i]][year])
//						{
//							importantPeople[person][1] += lettersByRecipient[personIds[i]][year].length;
//						}
//					}
//				}
//			}

			for(person in importantPeople)
			{
				var pids = window[person];
				for(var i = 0; i < pids.length; ++i)
				{
					if(correspondentStats[pids[i]])
					{
						importantPeople[person][0] = correspondentStats[pids[i]][0];
						importantPeople[person][1] = correspondentStats[pids[i]][1];
					}
				}
				var span = document.getElementById(person + "_stats");
				span.innerHTML = importantPeople[person][0] + "/" + importantPeople[person][1];
			}

			$("#sourcesStats span").remove();
			for(sourceId in filteredSources)
			{
				if(!sources[sourceId]) continue;
				var sourceName = sources[sourceId].name;
				var volume = filteredSources[sourceId];
				$("#sourcesStats").append("<span>" + sourceName + ": " + volume + " letters</span>");
			}
		}
	
		function getXHR()
		{
			if (window.XMLHttpRequest)
			{
			  return new XMLHttpRequest();
			}
			else // Internet Explorer 5/6
			{
			  return new ActiveXObject("Microsoft.XMLHTTP");
			}
		}
	
		function setDimensions()
		{
			var myWidth = 0, myHeight = 0;
			if( typeof( window.innerWidth ) == 'number' )
			{
			    //Non-IE
			    myWidth = window.innerWidth;
			    myHeight = window.innerHeight;
			}
			else
			{
				if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
				{
				    //IE 6+ in 'standards compliant mode'
				    myWidth = document.documentElement.clientWidth;
				    myHeight = document.documentElement.clientHeight;
				}
				else
				{
					if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
					{
					    //IE 4 compatible
					    myWidth = document.body.clientWidth;
					    myHeight = document.body.clientHeight;
					}
				}
			}
			
			
			width = Math.max( minWidth, myWidth -  (2 * padding) );
			height = Math.max( minHeight, myHeight - (2 * padding) );
	
//			timelineHeight = 200;
			sidePanelWidth = 400 - 2;
//			mainPanelWidth = width - 2;
			statsPanelHeight = 40;
//			mainPanelHeight = height - timelineHeight - padding - 2 - statsPanelHeight;
			
//			var mainPanelDiv = document.getElementById( "mainPanel" );
//			mainPanelDiv.style.width = "" + mainPanelWidth + "px";
//			mainPanelDiv.style.height = "" + mainPanelHeight + "px";
			
//			var timelinePanelContainer = document.getElementById( "timelineContainer" );
//			timelinePanelContainer.style.width = "" + mainPanelWidth + "px";
//			timelinePanelContainer.style.height = "" + timelineHeight + "px";
			
//			var statsPanel = document.getElementById( "statsPanel" );
//			statsPanel.style.width = "" + mainPanelWidth + "px";
		}
	
		window.onresize = setDimensions;
	</script> 
	<script type="text/javascript">
		function showLineDetails(lineId)
		{
			$("#screenTransparency").show();
			$("#screenTransparency").click(hideDetailsPane);
			$("#detailPanel").show();
			
			var left = ($(window).width() / 2) - 300;
			var top = ($(window).height() / 2) - 250;
			$("#detailPanel").offset({left: ($(window).width() / 2) - 1, top: ($(window).height() / 2) - 1});
			
			$("#detailPanel").animate({
				width: 600,
				height: 500,
				top: top,
				left: left
			}, 500, function(){ populateDetailsPane(lines[lineId], false); });
		}

		function showPersistentLineDetails(lineId)
		{
			if(!persistLines[lineId]) return;
			
			$("#screenTransparency").show();
			$("#screenTransparency").click(hideDetailsPane);
			$("#detailPanel").show();
			
			var left = ($(window).width() / 2) - 300;
			var top = ($(window).height() / 2) - 250;
			$("#detailPanel").offset({left: ($(window).width() / 2) - 1, top: ($(window).height() / 2) - 1});
			
			$("#detailPanel").animate({
				width: 600,
				height: 500,
				top: top,
				left: left
			}, 500, function(){ populateDetailsPane(persistLines[lineId], true); });
		}

		function populateDetailsPane(line, persistent)
		{
			var src = locations[line.src];
			var dst = locations[line.dst];
			src = src ? src.name : "Unknown";
			dst = dst ? dst.name : "Unknown";

			var yearText = persistent ? " (UNDATED)" : " (" + Math.round(startYear) + " - " + Math.round(endYear) + ")"
			$("#detailPanel").append("<h3>Letters from " + src + " to " + dst + yearText + "</h3>");
			var table = $('<table></table>');
			for(var i = 0; i < line.letters.length; ++i)
			{
				var letter = letterCorrespondents[line.letters[i]];
				if(letter)
				{
					var row = $('<tr></tr>');
					var author = people[[letter.author]];
					var recipient = people[[letter.recipient]];

					row.append("<td class='author'>" + author.name + "</td>");
					if(letterUrls && letterUrls[line.letters[i]])
					{
						row.append('<td class="letter"><a href="' + letterUrls[line.letters[i]] + '" target="_blank"><img src="script-icon_small.png"/></a></td>');
					}
					else
					{
						row.append('<td class="letter"><img src="script-icon_small.png"/></td>');
					}
					row.append("<td class='recipient'>" + recipient.name + "</td>");
					
					table.append(row);
				}
			}
			$("#detailPanel").append(table);
		}

		function hideDetailsPane()
		{
			var left = ($(window).width() / 2) - 1;
			var top = ($(window).height() / 2) - 1;
			$("#detailPanel h3").remove();
			$("#detailPanel table").remove();
			$("#detailPanel").hide(200, function(){ $("#detailPanel").height(2); $("#detailPanel").width(2); $("#screenTransparency").hide(); });
		}
	</script>
</div>
</body>
</html>