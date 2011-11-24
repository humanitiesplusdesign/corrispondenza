/*
 * MRofL Protovis MroflTimeline class
 * Prerequisites: protovis-3.x+, jquery-1.4.x+
 */

function MroflTimeline(container, minX, maxX, minY, maxY, data, onchange, owner)
{
	this.owner = owner;
	this.container = container;
	this.canvas = $('<div id="mrofl-timelinePanel">');
	$('#' + this.container).append(this.canvas);
	
	this.update(minX, maxX, minY, maxY, data, onchange);
}

MroflTimeline.prototype.init = function(obj)
{
	/** ********Draw timeline******** */
    var h_ticks = [];
    var ptr = 0;
    for( var i = obj.minX; i <= obj.maxX; ++i )
    {
    	if( i % 10 == 0 )
    	{
    		h_ticks[ptr++] = i;
    	}
    }
    var v_ticks = [];
    ptr = 0;
    for( var i = 1; i <= obj.maxY; i *= 10 )
    {
    	v_ticks[ptr++] = i;
    }
	
	obj.timeline = new pv.Panel()
	      .canvas( obj.canvas[0] )
	      .width( obj.width )
	      .height( obj.height )
	      .left( 0 )
	      .top( 0 );
    
    // var ruleColor = pv.color("#66a3d2");
    var ruleColor = pv.color("#175e94").alpha(0.1);
	obj.timeline.add( pv.Rule )
	        .data( h_ticks )
	        .strokeStyle( ruleColor )
	        .left( function(d){ return obj.xScale(d) + 30; })
	        .top( 10 )
	        .height( obj.height - 25 )
	        .anchor( "bottom" )
	        .add( pv.Label )
	        	.bottom( 0 )
	        	.textStyle("#444444");
	
	obj.timeline.add( pv.Rule )
	        .data( obj.yScale.ticks() )
	        .width(obj.width - 60)
	        .left(30)
	        .strokeStyle( ruleColor )
	        .bottom( function(d){ return obj.yScale(d) + 15; })
	        .anchor("left")
	        .add(pv.Label)
	        	.textStyle("#444444")
		        .bottom( function(d){ return obj.yScale(d); });
	
	/** *******Populate timeline******** */
	var barWidth = obj.xScale(obj.maxX) - obj.xScale(obj.maxX - 1) - 0.5;
	var timelineColors = [pv.color("#666666").alpha(.3), pv.color("#e65217").alpha(.6)];

	for(var year in obj.data[0])
	{
		var data = {'year':parseInt(year), 'volume':obj.data[0][year]};
		obj.timeline.add(pv.Bar)
			.data([data])
			.def('highlight', -1)
	        .left(function(d){ return obj.xScale(d.year) + 30; })
	        .bottom(15)
	        .width(barWidth)
	        .height(function(d){ return d.volume > 0 ? obj.yScale(d.volume) : 0; })
	        .fillStyle(function(d){ return this.index == this.highlight() ? timelineColors[0].alpha(1) : timelineColors[0]; })
	        .strokeStyle('none')
	        .lineWidth(0.5)
	        .cursor('pointer')
	        .title(function(d){ return d.year + " (Unplotted): " + d.volume + " letter(s)"; })
	        .event('mousemove', function(){ this.highlight(this.index); this.render(); })
	        .event('mouseout', function(){ this.highlight(-1); this.render(); })
	        .event('mouseover', pv.Behavior.tipsy({gravity: 's'}));
	}

	for(var year in obj.data[1])
	{
		var data = {'year':parseInt(year), 'volume':obj.data[1][year]};
		obj.timeline.add(pv.Bar)
			.data([data])
			.def('highlight', -1)
	        .left(function(d){ return obj.xScale(d.year) + 30; })
	        .bottom(15)
	        .width(barWidth)
	        .height(function(d){ return d.volume > 0 ? obj.yScale(d.volume) : 0; })
	        .fillStyle(function(d){ return this.index == this.highlight() ? timelineColors[1].alpha(1) : timelineColors[1]; })
	        .strokeStyle('none')
	        .lineWidth(0.5)
	        .cursor('pointer')
	        .title(function(d){ return d.year + " (Plotted): " + d.volume + " letter(s)"; })
	        .event('mousemove', function(){ this.highlight(this.index); this.render(); })
	        .event('mouseout', function(){ this.highlight(-1); this.render(); })
	        .event('mouseover', pv.Behavior.tipsy({gravity: 's'}));
	}
	
//	obj.timeline.add(pv.Bar)
//		.data(obj.data[1])
//		.def('highlight', -1)
//        .left(function(d){ return obj.xScale(this.index + obj.minX) + 30; })
//        .bottom(15)
//        .width(barWidth)
//        .height(function(d){ return d > 0 ? obj.yScale(d) : 0; })
//        .fillStyle(function(d){ return this.index == this.highlight() ? timelineColors[1].alpha(1) : timelineColors[1]; })
//        .strokeStyle('none')
//        .lineWidth(0.5)
//        .cursor('pointer')
//        .title(function(d){ return "Plotted: " + d + " letter(s)"; })
//        .event('mousemove', function(){ this.highlight(this.index); this.render(); })
//        .event('mouseout', function(){ this.highlight(-1); this.render(); })
//        .event('mouseover', pv.Behavior.tipsy({gravity: 's'}));

    /** *******Draw timeline filter bar******* */
    var filterData = {x: obj.xScale(obj.start), dx: obj.xScale(obj.end) - obj.xScale(obj.start)};
//    fx = pv.Scale.linear().range(0, obj.height - 85);
    
    var filterContainer = obj.timeline.add(pv.Panel)
    			.left(30)
    			.width(obj.width - 60)
    			.height(obj.height);
    
    obj.tlFilter = filterContainer.add(pv.Bar)
				.data([filterData])
			    .top(10)
			    .left(function(d){ return d.x; })
			    .width(function(d){ return d.dx; })
			    .height(obj.height-25)
	            .lineWidth(1)
	            .strokeStyle(pv.color("#6d87d6").alpha(.5))
			    .fillStyle(pv.color("#6d87d6").alpha(.15))
			    .cursor("move")
			    .event("mouseover", function(){ obj.showYears(true, true); })
			    .event("mouseout", function(){ obj.hideYears(); })
			    .event("mousedown", pv.Behavior.drag())
			    .event("drag", function(d){
			    	obj.start = obj.xScale.invert(d.x);
			    	obj.end = obj.xScale.invert(d.x + d.dx);
			    	if(obj.tlResizeR.data().x > obj.tlResizeL.data().x)
			    	{
				    	obj.tlResizeL.data([{x: obj.xScale(obj.start)}]);
				    	obj.tlResizeR.data([{x: obj.xScale(obj.end)}]);
			    	}
			    	else
			    	{
				    	obj.tlResizeL.data([{x: obj.xScale(obj.end)}]);
				    	obj.tlResizeR.data([{x: obj.xScale(obj.start)}]);
			    	}
			    	obj.tlResizeL.render();
			    	obj.tlResizeR.render();
	    			obj.showYears(true, true);
	    			obj.owner[obj.onchange]();
			    })
		    	.event("dragend", function(d){
//	    			obj.owner[obj.onchange]();
		    	});

	var resizeLData = {x: obj.xScale(obj.start)};
    obj.tlResizeL = filterContainer.add(pv.Bar)
			.data([resizeLData])
			.def("highlight", false)
            .top(10)
            .left(function(d){ return d.x - 8; })
		    .height(obj.height-25)
            .width(16)
            .lineWidth(1)
            .strokeStyle(pv.color("#6d87d6").alpha(.5))
            .fillStyle(function(d){ return obj.tlResizeL.highlight() ? pv.color("#555555").alpha(.3) : pv.color("#555555").alpha(.2); })
    		.cursor("ew-resize")
            .event("mouseover", function(){ obj.tlResizeL.highlight(true); obj.showYears(true, true); obj.tlResizeL.render(); return pv.Behavior.tipsy(); })
            .event("mouseout", function(){ obj.tlResizeL.highlight(false); obj.hideYears(); obj.tlResizeL.render(); })
    		.event("mousedown", pv.Behavior.drag())
    		.event("drag", function(d){
	    			d_2 = obj.tlResizeR.data();
	    			var left = Math.min(d.x, d_2.x);
	    			var right = Math.max(d.x, d_2.x);
	    			obj.start = obj.xScale.invert(left);
	    			obj.end = obj.xScale.invert(right);
	    			obj.tlFilter.data([{x: left, dx: right-left}]);
	    			obj.tlFilter.render();
	    			obj.showYears(true, true);
	    			obj.owner[obj.onchange]();
    		})
	    	.event("dragend", function(d){
//    			obj.owner[obj.onchange]();
	    	});

	var resizeRData = {x: obj.xScale(obj.end)};
    obj.tlResizeR = filterContainer.add(pv.Bar)
			.data([resizeRData])
			.def("highlight", false)
            .top(10)
            .left(function(d){ return d.x - 8; })
		    .height(obj.height-25)
            .width(16)
            .lineWidth(1)
            .strokeStyle(pv.color("#6d87d6").alpha(.5))
            .fillStyle(function(d){ return obj.tlResizeR.highlight() ? pv.color("#555555").alpha(.3) : pv.color("#555555").alpha(.2); })
    		.cursor("ew-resize")
            .event("mouseover", function(){ obj.tlResizeR.highlight(true); obj.showYears(true, true); obj.tlResizeR.render(); return pv.Behavior.tipsy(); })
            .event("mouseout", function(){ obj.tlResizeR.highlight(false); obj.hideYears(); obj.tlResizeR.render(); })
    		.event("mousedown", pv.Behavior.drag())
    		.event("drag", function(d){
    			d_2 = obj.tlResizeL.data();
    			var left = Math.min(d.x, d_2.x);
    			var right = Math.max(d.x, d_2.x);
    			obj.start = obj.xScale.invert(left);
    			obj.end = obj.xScale.invert(right);
    			obj.tlFilter.data([{x: left, dx: right-left}]);
    			obj.tlFilter.render();
    			obj.showYears(true, true);
    			obj.owner[obj.onchange]();
	    	})
	    	.event("dragend", function(d){
//    			obj.owner[obj.onchange]();
	    	});
			
	obj.timeline.render();
    
    obj.canvas.append($('<div class="yearDisplay start">'));
    obj.canvas.append($('<div class="yearDisplay end">'));
    $('#' + obj.canvas.attr('id') + ' .yearDisplay').css({
    	'position': 'absolute',
    	'top': '-15px',
    	'z-index': '10000',
    	'line-height': '25px',
    	'height': '25px',
    	'width': '40px',
    	'padding': '0px',
    	'margin': '0px',
    	'text-align': 'center',
    	'color': '#666666',
    	'font-size': '22px',
		'font-family': '"Impact", "Charcoal", sans-serif',
    	'dsiplay': 'block'
    });
    $('#' + obj.canvas.attr('id') + ' .yearDisplay').hide();
};

MroflTimeline.prototype.update = function(minX, maxX, minY, maxY, data, onchange)
{
	this.minX = parseInt(minX);
	this.minX = isNaN(this.minX) ? 1600 : this.minX;
	this.maxX = parseInt(maxX);
	this.maxX = isNaN(this.maxX) ? 1800 : this.maxX;
	this.maxX = Math.min(this.maxX, 1850);

	this.minY = parseInt(minY);
	this.minY = isNaN(this.minY) ? 0 : this.minY;
	this.maxY = parseInt(maxY);
	this.maxY = isNaN(this.maxY) ? 2000 : this.maxY;
	
	this.data = data;
	this.onchange = onchange;
	
	var dif = this.maxX - this.minX;
	this.start = (this.maxX - this.minX) / 2 + this.minX - (0.1 * dif / 2);
	this.end = this.start + (0.1 * dif);
	
	this.width = $('#' + this.container).width();
	this.height = $('#' + this.container).height();
	
    this.xScale = pv.Scale.linear(this.minX, this.maxX).range(0, this.width - 60);
    this.yScale = pv.Scale.linear(0, this.maxY).range(0, this.height - 25).nice();
    
    this.init(this);
};

MroflTimeline.prototype.resize = function()
{
	this.width = $('#' + this.container).width();
	this.height = $('#' + this.container).height();
	
    this.xScale = pv.Scale.linear(this.minX, this.maxX).range(0, this.width - 60);
    this.yScale = pv.Scale.linear(0, this.maxY).range(0, this.height - 25).nice();
    this.init(this);
};

MroflTimeline.prototype.showYears = function(showStart, showEnd)
{
	showStart = showStart == null || showStart == undefined ? true : showStart;
	showEnd = showEnd == null || showEnd == undefined ? true : showEnd;
	
	var start = this.xScale(this.start) + 30 - 20;
	var end = this.xScale(this.end) + 30 - 20;
	if(end - (start + 40) < 10)
	{
		var sep = -(end - (start + 40)) + 10;
		start -= sep / 2;
		end += sep / 2;
	}
    $('#' + this.canvas.attr('id') + ' .yearDisplay.start').css({'left':start + 'px'});
    $('#' + this.canvas.attr('id') + ' .yearDisplay.end').css({'left':end + 'px'});

	start = Math.floor(this.start);
	end = Math.floor(this.end);
    $('#' + this.canvas.attr('id') + ' .yearDisplay.start').text(start);
    $('#' + this.canvas.attr('id') + ' .yearDisplay.end').text(end);

    if(showStart)
    {
    	$('#' + this.canvas.attr('id') + ' .yearDisplay.start').stop();
    	$('#' + this.canvas.attr('id') + ' .yearDisplay.start').fadeTo(100, 1);
    }
    if(showEnd)
    {
    	$('#' + this.canvas.attr('id') + ' .yearDisplay.end').stop();
    	$('#' + this.canvas.attr('id') + ' .yearDisplay.end').fadeTo(100, 1);
    }
};

MroflTimeline.prototype.hideYears = function()
{
    $('#' + this.canvas.attr('id') + ' .yearDisplay').stop();
    $('#' + this.canvas.attr('id') + ' .yearDisplay').fadeTo(200, 0);
};

MroflTimeline.prototype.getMin = function()
{
	return this.minX;
};

MroflTimeline.prototype.setMin = function(year)
{
	year = parseInt(year);
	if(isNaN(year)) return;
	this.minX = year;
};

MroflTimeline.prototype.getMax = function()
{
	return this.maxX;
};

MroflTimeline.prototype.setMax = function(year)
{
	year = parseInt(year);
	if(isNaN(year)) return;
	this.maxX = year;
};

MroflTimeline.prototype.getStart = function()
{
	return this.start;
};

MroflTimeline.prototype.setStart = function(start)
{
	start = parseInt(start);
	this.start = isNaN(start) ? this.start : start;
};

MroflTimeline.prototype.getEnd = function()
{
	return this.end;
};

MroflTimeline.prototype.setEnd = function(end)
{
	end = parseInt(end);
	this.end = isNaN(end) ? this.end : end;
};




