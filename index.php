<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Republic Of Letters Network Visualization</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="stylesheets/main.css"/>
<link rel="stylesheet" type="text/css" href="stylesheets/mrofl-map.css"/>
<link rel="stylesheet" type="text/css" href="stylesheets/tipsy.css" />

<script type="text/javascript" src="js/json-minified.js"></script>
<script type="text/javascript" src="js/mrofl-timeline.js"></script>
<script type="text/javascript" src="js/mrofl-map.js"></script>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
<script type="text/javascript" src="js/polymaps/polymaps.min.js"></script>
<script type="text/javascript" src="js/protovis-3.2/protovis-d3.2.js"></script>
<script type="text/javascript" src="js/jquery.tipsy.js"></script>
<script type="text/javascript" src="js/pv.tipsy.js"></script>
<script type="text/javascript">
</script>
</head>
<body onload="initMapVis()">
<div class="loader">
	<table>
		<tr></tr>
		<tr>
			<td>
				<img src="images/loading.gif" />
			</td>
		</tr>
		<tr></tr>
	</table>
</div>
<div id="mainContainer" align="center">
	<div id="mapContainer"></div>
	<div class="controlPanel">
		<div class="transparency"></div>
		<div class="content">
			<h2>LAYERS</h2>
			<h3><a href="javascript:addLayer()" title="Click to add new layer">+ Add New Layer</a></h3>
			<div class="layers">
			</div>
		</div>
		<div class="pulloutTab">
			<div class="transparency"></div>
			&larr;
		</div>
	</div>
</div>

<script type="text/javascript">
	var controlPanelVisible = true;
	$('.controlPanel .pulloutTab').click(function()
        {
        	if(controlPanelVisible)
        	{
        		$('.controlPanel').animate({left:'-320px'}, 250);
        		$('.controlPanel .pulloutTab').html('&rarr;');
        		controlPanelVisible = false;
        	}
        	else
        	{
        		$('.controlPanel').animate({left:'0px'}, 250);
        		$('.controlPanel .pulloutTab').html('&larr;');
        		controlPanelVisible = true;
        	}
        }
    );

	var mroflMap = null;
	var layers = [];
	function initMapVis()
	{
		mroflMap = new MroflMap('mapContainer');
	}

	function addLayer()
	{
		var layerNum = layers.length;
		var layer = $('<div class="layer layer_' + layerNum + '">');
		var layerNameInputId = 'layer_' + layerNum + '_name';
		var layerRefreshButtonId = 'layer_' + layerNum + '_btRefresh';
		layer.append($('<h4>Layer ' + (layerNum + 1) + '</h4>'));
		layer.append($('<div><input type="text" id="' + layerNameInputId + '" /></div>'));
		layer.append($('<div><button type="button" id="' + layerRefreshButtonId + '">Refresh</button></div>'));
		$('.controlPanel .layers').append(layer);
		layers.push({'layer':layer, 'id':-1});

		$('#' + layerNameInputId).focus();

		$('#' + layerRefreshButtonId).click(function(){
			var num = $(this).attr('id').split('_');
			num = parseInt(num[1]);
			refreshLayer(num);
		});
		
		$('#' + layerNameInputId).keypress(function(event)
        {
	        if(event.keyCode == 13)
	        {
	            $('#' + layerRefreshButtonId).focus();
	            $('#' + layerRefreshButtonId).click();
	        }
        });
	}

	function refreshLayer(layerNum)
	{
		showLoader();
		var curLayer = layers[layerNum];
		if(curLayer.id < 0)
		{
			curLayer.id = mroflMap.addLayer($('#layer_' + layerNum + '_name').val());
		}
		else
		{
			mroflMap.refreshLayer(curLayer.id, $('#layer_' + layerNum + '_name').val());
		}
		hideLoader();
	}

	function showLoader()
	{
		$('.loader').show();
	}

	function hideLoader()
	{
		$('.loader').hide();
	}
</script>

</body>
</html>