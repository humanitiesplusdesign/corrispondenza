function MroflMap(canvas)
{
	this.canvas = $('#' + canvas);
	this.content = null;
	this.topLayer = 0;
	this.layers = {};
	
	///POLYMAPS
    this.map = null;
	this.mapCanvas = null;
	this.po = null;
	this._tile = null;
	this._projection = null;
	this.minZoom = 3;
	this.maxZoom = 10;
	this.defaultZoom = 3;
	
	///DATA
	this.letters = null;
	this.people = null;
	this.locations = null;
	this.visibleLocations = {};
	this.sources = null;
	
	this.letterSources = {};
	this.letterAuthors = {};
	this.letterRecipients = {};
	this.letterUrls = {};
	this.srcLocs = {};
	this.dstLocs = {};

	this.links = null;
	this.linesOrig = [];
	this.lines = [];
	this.persistLines = [];
	this.lineEcc = {};
	this.showLines = true;
	this.showPersistentLines = true;
	
	this.dotsOrig = [];
	this.dots = [];
	this.seenLocs = {};
	
	this.incompletes = [];
	this.volumes = [];

	// Defaults
	this.minYear = 1600;
	this.maxYear = 1850;
	this.maxLetters = 200;
	this.startYear = 1730;
	this.endYear = 1760;
	this.maxPlottableLetters = 0;

	// Line size limits
	this.minLineWidth = 2.0;
	this.maxLineWidth = 30.0;

	// UI dimensions
	this.minWidth = 600;
	this.minHeight = 400;
	this.timelineHeight = 200;

	// Protovis objects
	this.mapVis;
	this.timeline;
	this.colorScale;
	this.visReady = false;
	this.mapReady = false;
	this.timelineReady = false;
	
	// Scales
	this.nodeScale = pv.Scale.log(1, 200).range(5, 40);
	this.zoomScale = pv.Scale.linear(this.minZoom, this.maxZoom).range(0.5, 1);

	this.init();
}

MroflMap.prototype.init = function()
{
	this.initData();
	this.initDOM();
	this.initPMaps();
	this.initLocationsLayer();
};

MroflMap.prototype.initData = function()
{
	$.ajax({
		url:'data/get-lines.php',
		type:'GET',
		async:true,
		dataType:'text',
		context:this,
		success:function(data){
			data = jsonParse(data);
			this.lineEcc = data.lines;
		},
		error:function(){
		}
	});

	$.ajax({
		url:'data/get-source-locations.php',
		type:'GET',
		async:true,
		dataType:'text',
		context:this,
		success:function(data){
			data = jsonParse(data);
			this.srcLocs = data.letters;
		},
		error:function(){
		}
	});

	$.ajax({
		url:'data/get-destination-locations.php',
		type:'GET',
		async:true,
		dataType:'text',
		context:this,
		success:function(data){
			data = jsonParse(data);
			this.dstLocs = data.letters;
		},
		error:function(){
		}
	});
};

MroflMap.prototype.initDOM = function()
{
	this.content = $('<div class="mrofl-map">');
	
	this.mapCanvas = $('<div class="map">');
	this.content.append(this.mapCanvas);
	
	this.timelineContainer = $('<div class="timelineContainer" id="mrofl-map_timelineContainer">');
	this.timelineContainer.append($('<div class="transparency">'));
	this.content.append(this.timelineContainer);
	
	this.content.append(this.controlPanel);
	
	this.content.append('<div width="32" height="32" class="minimizer" title="Toggle full-screen"></div>');	
	this.content.append('<div width="32" height="32" class="maximizer" title="Toggle full-screen"></div>');
	
	this.canvas.append(this.content);
	
	this.timeline = new MroflTimeline('mrofl-map_timelineContainer', this.minYear, this.maxYear, 0, this.maxLetters, [this.incompletes, this.volumes], 'filter', this);

	var obj = this;
	$('.mrofl-map .minimizer').click(function(){ obj.minimize(); });
	$('.mrofl-map .maximizer').click(function(){ obj.maximize(); });
};

MroflMap.prototype.initPMaps = function()
{
	this.po = org.polymaps;
	this.map = this.po.map()
	    .container(this.mapCanvas[0].appendChild(this.po.svg("svg")).appendChild(this.po.svg('g')))
	    .center({lat: 40, lon: -30})
	    .zoom(this.defaultZoom)
	    .zoomRange([this.minZoom, this.maxZoom])
	    .add(this.po.interact());

	this.map.add(this.po.image()
	    .url(this.po.url("http://{S}tile.cloudmade.com"
	    + "/79c430376d494b3eb2de8b853e6d9765" // http://cloudmade.com/register
	    + "/20760/256/{Z}/{X}/{Y}.png")
	    .hosts(["a.", "b.", "c.", ""])));
	
	this.map.add(this.po.compass()
	    .pan("none"));
};

/*
 * Add a new protovis layer to the map.
 * Returns the layer ID for the new layer.
 */
MroflMap.prototype.addLayer = function(person)
{
	var response = $.ajax({
		url:'data/get-map-data.php',
		type:'GET',
		data:'name=' + person,
		async:false,
		dataType:'text'
	});
	
	var response = jsonParse(response.responseText);
	this.minYear = response.minYear;
	this.maxYear = response.maxYear;
	this.maxLetters = response.maxLetters;
	this.maxPlottableLetters = Math.max(this.maxPlottableLetters, response.maxPlottableLetters);
	this.incompletes = response.incompletes;
	this.volumes = response.volumes;
	if(this.maxYear >= this.minYear)
	{
		this.timeline.update(this.minYear, this.maxYear, 0, this.maxLetters, [this.incompletes, this.volumes], 'filter', this);
	}
	else
	{
		this.timeline.update(1600, 1800, 0, 200, [{}, {}], function(){});
	}
	
	var layerID = this.topLayer++;
	this.initPVLayer(layerID, response.letters, response.undatedLetters);
	return layerID;
};

/*
 * Refreshes the specified protovis layer using the
 * filter data given.
 */
MroflMap.prototype.refreshLayer = function(layerID, person)
{
	var response = $.ajax({
		url:'data/get-map-data.php',
		type:'GET',
		data:'name=' + person,
		async:false,
		dataType:'text'
	});
	
	var response = jsonParse(response.responseText);
	this.minYear = response.minYear;
	this.maxYear = response.maxYear;
	this.maxLetters = response.maxLetters;
	this.maxPlottableLetters = Math.max(this.maxPlottableLetters, response.maxPlottableLetters);
	this.incompletes = response.incompletes;
	this.volumes = response.volumes;
	if(this.maxYear >= this.minYear)
	{
		this.timeline.update(this.minYear, this.maxYear, 0, this.maxLetters, [this.incompletes, this.volumes], 'filter', this);
	}
	else
	{
		this.timeline.update(1600, 1800, 0, 200, [{}, {}], function(){});
	}
	
	this.refreshPVLayer(layerID, response.letters, response.undatedLetters);
};

/*
 * Removes the specified layer from the layer stack.
 */
MroflMap.prototype.removeLayer = function(layerId)
{
	
};

MroflMap.prototype.initPMapsLayer = function(layerID, updateFunc)
{
	var newLayer = this.po.layer(this.mapUpdateWrap(this, layerID, updateFunc)).tile(false);
	this.map.add(newLayer);
	return newLayer;
};

MroflMap.prototype.mapUpdateWrap = function(obj, layerID, updateFunc)
{
	return function(tile, projection) {
		obj[updateFunc](layerID, tile, projection);
	};
};

MroflMap.prototype.initLocationsLayer = function()
{
	$.ajax({
		url:'data/get-locations.php',
		type:'GET',
		async:true,
		dataType:'text',
		context:this,
		success:function(data){
			data = jsonParse(data);
			this.locations = data.locations;
			var dots = data.dots;
			
			var layerID = this.topLayer++;
			var newLayer = {};
			newLayer.vis = new pv.Panel();
			newLayer.dots = this.dotsOrig = dots;
			newLayer.tile = null;
			newLayer.projection = null;
			this.layers[layerID] = newLayer;

			this.updateLocationsLayer(layerID);
			this.layers[layerID].mapLayer = this.initPMapsLayer(layerID, 'drawLocationsLayer');
		},
		error:function(){
		}
	});
};

MroflMap.prototype.updateLocationsLayer = function(layerID)
{
	var curLayer = this.layers[layerID];
	var dots = {};
	for(i in this.layers)
	{
		if(i == 0) continue;
		
		var layer = this.layers[i];
		for(j in layer.dots)
		{
			if(!this.dotsOrig[j]) continue;

			if(!dots[j])
			{
				dots[j] = {
						coords:this.dotsOrig[j].coords,
						name:this.dotsOrig[j].name,
						volume:0
				};
			}
			dots[j].volume += layer.dots[j].volume ? layer.dots[j].volume : 0;
		}
	}
	
	curLayer.dots = dots;
};

MroflMap.prototype.drawLocationsLayer = function(layerID, tile, projection)
{
	var obj = this;
	var curLayer = this.layers[layerID];
	var g;
	if(curLayer.tile != tile)
	{
		curLayer.tile = tile;
		curLayer.projection = projection;
		g = curLayer.tile.element = this.po.svg("g");
	}
	else
	{
		g = curLayer.tile.element;
	}

	curLayer.vis = new pv.Panel()
					.canvas(g)
					.width(obj.map.size().x)
					.height(obj.map.size().y)
					.left(0)
					.top(0);
	
	for(locID in curLayer.dots)
	{
		var data = [curLayer.dots[locID]];
		curLayer.vis.add(pv.Dot)
				.data(data)
				.left(function(d){ return curLayer.projection(curLayer.tile).locationPoint({'lat':d.coords[0], 'lon':d.coords[1]}).x; })
				.top(function(d){ return curLayer.projection(curLayer.tile).locationPoint({'lat':d.coords[0], 'lon':d.coords[1]}).y; })
				.radius(function(d){ return obj.nodeScale(d.volume)/* * obj.zoomScale(obj.map.zoom())*/; })
				.lineWidth(2)
				.strokeStyle(pv.color('#1e78b4').alpha(.3))
				.fillStyle(pv.color('#1e78b4').alpha(.08))
				.cursor("pointer")
				.visible(function(d){ return d.volume > 0; })
				.title(function(d){ return (d.name ? d.name : 'Unknown') + ': ' + d.volume + ' letter(s)'; });
	}
	
	curLayer.vis.render();
};

MroflMap.prototype.initPVLayer = function(layerID, letters, undatedLetters)
{
	var newLayer = {};
	newLayer.vis = new pv.Panel();
	newLayer.dots = {};
	newLayer.lines = [];
	newLayer.persistentLines = [];
	newLayer.letters = letters;
	newLayer.undatedLetters = undatedLetters;
	newLayer.tile = null;
	newLayer.projection = null;
	this.layers[layerID] = newLayer;
	
	this.updatePVLayer(layerID);
	this.layers[layerID].mapLayer = this.initPMapsLayer(layerID, 'drawPVLayer');
	this.updateLocationsLayer(0);
	this.layers[0].mapLayer.reload();
};

MroflMap.prototype.refreshPVLayer = function(layerID, letters, undatedLetters)
{
	this.layers[layerID].vis = new pv.Panel();
	this.layers[layerID].dots = {};
	this.layers[layerID].lines = [];
	this.layers[layerID].persistentLines = [];
	this.layers[layerID].letters = letters;
	this.layers[layerID].undatedLetters = undatedLetters;
	
	this.updatePVLayer(layerID);
	this.updateLocationsLayer(0);
	
	this.layers[layerID].mapLayer.reload();
	this.layers[0].mapLayer.reload();
};

MroflMap.prototype.filter = function()
{
	for(var i in this.layers)
	{
		if(i == 0) continue;
		this.updatePVLayer(i);
		this.layers[i].mapLayer.reload();
	}
	this.updateLocationsLayer(0);
	this.layers[0].mapLayer.reload();
};

MroflMap.prototype.updatePVLayer = function(layerID)
{
	this.layers[layerID].dots = {};
	this.layers[layerID].lines = [];
	this.layers[layerID].persistentLines = [];
	var seen = {};
	var undatedLetters = this.layers[layerID].undatedLetters;
	for(var i in undatedLetters)
	{
		if(this.srcLocs[undatedLetters[i]] && this.dstLocs[undatedLetters[i]])
		{
			var lineID = 'l' + this.srcLocs[undatedLetters[i]] + '_' + this.dstLocs[undatedLetters[i]];
			var line = this.lineEcc[lineID];
			var src = this.dotsOrig[line.src];
			var dst = this.dotsOrig[line.dst];
			
			if(!src || !dst) continue;

			if(!seen[line.src])
			{
				seen[line.src] = true;
				var dot = {
						coords:src.coords,
						name:src.name,
						volume:0
				};
				this.layers[layerID].dots[line.src] = dot;
			}
			++this.layers[layerID].dots[line.src].volume;

			if(!seen[line.dst])
			{
				seen[line.dst] = true;
				var dot = {
						coords:dst.coords,
						name:dst.name,
						volume:0
				};
				this.layers[layerID].dots[line.dst] = dot;
			}
			++this.layers[layerID].dots[line.dst].volume;
			
			if(!this.layers[layerID].persistentLines[lineID])
			{
				this.layers[layerID].persistentLines[lineID] = ([
			         {
			        	coords:{lat:src.coords[0], lon:src.coords[1]},
			        	name: src.name,
			        	volume: 0
			         },
			         {
			        	coords:{lat:dst.coords[0], lon:dst.coords[1]},
			        	name: dst.name,
			        	volume: 0
			         }
				]);
			}
			++this.layers[layerID].persistentLines[lineID][0].volume;
			++this.layers[layerID].persistentLines[lineID][1].volume;
		}
	}

	var startYear = Math.floor(this.timeline.getStart());
	var endYear = Math.ceil(this.timeline.getEnd());
	for(var year = startYear; year < endYear; ++year)
	{
		if(this.layers[layerID].letters[year])
		{
			var letters = this.layers[layerID].letters[year];
			for(var i in letters)
			{
				if(this.srcLocs[letters[i]] && this.dstLocs[letters[i]])
				{
					var lineID = 'l' + this.srcLocs[letters[i]] + '_' + this.dstLocs[letters[i]];
					var line = this.lineEcc[lineID];
					var src = this.dotsOrig[line.src];
					var dst = this.dotsOrig[line.dst];
					
					if(!src || !dst) continue;

					if(!seen[line.src])
					{
						seen[line.src] = true;
						var dot = {
								coords:src.coords,
								name:src.name,
								volume:0
						};
						this.layers[layerID].dots[line.src] = dot;
					}
					++this.layers[layerID].dots[line.src].volume;

					if(!seen[line.dst])
					{
						seen[line.dst] = true;
						var dot = {
								coords:dst.coords,
								name:dst.name,
								volume:0
						};
						this.layers[layerID].dots[line.dst] = dot;
					}
					++this.layers[layerID].dots[line.dst].volume;
					
					if(!this.layers[layerID].lines[lineID])
					{
						this.layers[layerID].lines[lineID] = ([
		                     {
		                    	coords:{lat:src.coords[0], lon:src.coords[1]},
		                    	name: src.name,
		                    	volume: 0
		                     },
		                     {
		                    	coords:{lat:dst.coords[0], lon:dst.coords[1]},
		                    	name: dst.name,
		                    	volume: 0
		                     }
						]);
					}
					++this.layers[layerID].lines[lineID][0].volume;
					++this.layers[layerID].lines[lineID][1].volume;
				}
			}
		}
	}
};

MroflMap.prototype.drawPVLayer = function(layerID, tile, projection)
{
	var obj = this;
	var curLayer = this.layers[layerID];
	var g;
	if(curLayer.tile != tile)
	{
		curLayer.tile = tile;
		curLayer.projection = projection;
		g = curLayer.tile.element = this.po.svg("g");
	}
	else
	{
		g = curLayer.tile.element;
	}
	
	var lines = curLayer.lines;
	var persistentLines = curLayer.persistentLines;

	curLayer.vis.children = [];
	curLayer.vis = new pv.Panel()
					.canvas(g)
					.width(obj.map.size().x)
					.height(obj.map.size().y)
					.left(0)
					.top(0);

    var grayLine = pv.color("#333333").alpha(.05);
	for(i in curLayer.persistentLines)
	{
		var line = curLayer.persistentLines[i];
		
		var thickness = this.minLineWidth + ( (line[0].volume / this.maxPlottableLetters) * (this.maxLineWidth - this.minLineWidth) );
		var eccentricity = this.lineEcc[i] ? this.lineEcc[i].ecc : 0.5;
        curLayer.vis.add(pv.Line)
           			    .data(line)
						.def("id", i)
						.left(function(d){ return curLayer.projection(curLayer.tile).locationPoint(d.coords).x; })
						.top(function(d){ return curLayer.projection(curLayer.tile).locationPoint(d.coords).y; })
           			    .lineWidth( thickness )
           			    .interpolate( "polar" )
           			    .eccentricity(eccentricity)
           			    .cursor("pointer")
           			    .title("FROM: " + line[0].name + " TO: " + line[1].name)
           			    .strokeStyle(grayLine);
//						.event("click", function(){ showPersistentLineDetails(this.id()); });
	}
	
	for(i in curLayer.lines)
	{
		var line = curLayer.lines[i];
		
		var thickness = this.minLineWidth + ( (line[0].volume / this.maxPlottableLetters) * (this.maxLineWidth - this.minLineWidth) );
		var eccentricity = this.lineEcc[i] ? this.lineEcc[i].ecc : 0.5;
        curLayer.vis.add(pv.Line)
           			    .data(line)
						.def("id", i)
						.left(function(d){ return curLayer.projection(curLayer.tile).locationPoint(d.coords).x; })
						.top(function(d){ return curLayer.projection(curLayer.tile).locationPoint(d.coords).y; })
           			    .lineWidth( thickness )
           			    .interpolate( "polar" )
           			    .eccentricity(eccentricity)
           			    .cursor("pointer")
           			    .title("FROM: " + line[0].name + " TO: " + line[1].name)
           			    .strokeStyle(pv.color('#ff831d').alpha(0.4));
//						.event("click", function(){ showPersistentLineDetails(this.id()); });
	}
//	curLayer.vis.add(pv.Dot)
//		.data([{lat:0,lon:0}])
//		.left(function(d){ curLayer.projection(curLayer.tile).locationPoint(d).x)
//		.top(function(d) curLayer.projection(curLayer.tile).locationPoint(d.coords).y)
//		.radius(200)
//		.fillStyle(pv.color('#666666').alpha(0.6));
	curLayer.vis.render();
};

MroflMap.prototype.minMaxToggle = function(obj)
{
	if(obj.content.hasClass('max'))
	{
		return function() {
			obj.minimize();
		};
	}
	else
	{
		return function() {
			obj.maximize();
		};
	}
};

MroflMap.prototype.maximize = function()
{
	this.content.addClass('max');
	this.resize();
};

MroflMap.prototype.minimize = function()
{
	this.content.removeClass('max');
	this.resize();
};

MroflMap.prototype.resize = function()
{
	this.timeline.resize();
	this.map.resize();
};

MroflMap.prototype.showLineDetails = function(lineId)
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
};

MroflMap.prototype.showPersistentLineDetails = function(lineId)
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
};

MroflMap.prototype.populateDetailsPane = function(line, persistent)
{
	var src = locations[line.src];
	var dst = locations[line.dst];
	src = src ? src.name : "Unknown";
	dst = dst ? dst.name : "Unknown";

	var yearText = persistent ? " (UNDATED)" : " (" + Math.floor(timeline.getStart()) + " - " + Math.floor(timeline.getEnd()) + ")";
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
};

MroflMap.prototype.hideDetailsPane = function()
{
	var left = ($(window).width() / 2) - 1;
	var top = ($(window).height() / 2) - 1;
	$("#detailPanel h3").remove();
	$("#detailPanel table").remove();
	$("#detailPanel").hide(200, function(){ $("#detailPanel").height(2); $("#detailPanel").width(2); $("#screenTransparency").hide(); });
};