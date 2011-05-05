<?php
header("Content-type: text/plain");

defined('ENTITY_LOCATION') || define('ENTITY_LOCATION', 2);

require_once 'location-scales.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$locations = array();
$nodes = array();
$locationsByScale = array();
$heirarchies = array();
$dots = array();

$rows = getAllLocations();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$scale = ($uid == GLOBAL_SCALE) ? GLOBAL_SCALE : intval($row['scale']);
	$row['scale'] = $scale;
	$row['coords'] = !!$row['coords'] ? explode(',', $row['coords']) : null;
	if(!!$row['coords'])
	{
		$row['coords'][0] = floatval($row['coords'][0]);
		$row['coords'][1] = floatval($row['coords'][1]);
		
		$dots[$uid] = array(
			'visible' => false,
			'coords'  => $row['coords'],
			'name'    => $row['name']
		);
	}
	else
	{
		$row['coords'] = null;
	}
	
	if(isset($locations[$uid]))
	{
		if($row['scale'] < $locations[$uid]['scale'])
		{
			$locations[$uid] = $row;
		}
	}
	else
	{
		$locations[$uid] = $row;
	}
	
	if($scale)
	{
		if(!isset($locationsByScale))
		{
			$locationsByScale[$scale] = array();
		}
		
		if(!isset($nodes[$uid]))
		{
			$nodes[$uid] = 0;
			$locationsByScale[$scale][] = $uid;
		}
	}
	
	$parentUid = $row['located_under'];
	if(!isset($heirarchies[$parentUid]))
	{
		$heirarchies[$parentUid] = array();
	}
	if($uid != $parentUid)
	{
		$heirarchies[$parentUid][] = $uid;
	}
}

$response = array();
$response['locations'] = $locations;
$response['locationsByScale'] = $locationsByScale;
$response['nodes'] = $nodes;
$response['heirarchies'] = $heirarchies;
$response['dots'] = $dots;
echo json_encode($response);

function getAllLocations()
{
	$locationTypeEntityId = ENTITY_LOCATION;
	$query = <<<QUERY
SELECT

`links`.`entity_id` AS `uid`,
`entities`.`name_vern` AS `name`,
`coords`.`data` AS `coords`,
`scales`.`data` AS `scale`,
`containers`.`target_id` AS `located_under`

FROM `links`

LEFT JOIN `entities`
ON `entities`.`uid` = `links`.`entity_id`

LEFT JOIN `links` AS `coords`
ON `coords`.`entity_id` = `links`.`entity_id` AND `coords`.`link_type` = "has_latlong"

LEFT JOIN `links` AS `containers`
ON `containers`.`entity_id` = `links`.`entity_id` AND `containers`.`link_type` = "located_under"

LEFT JOIN `links` AS `scales`
ON `scales`.`entity_id` = `links`.`entity_id` AND `scales`.`link_type` = "has_scale"

WHERE

`links`.`link_type` = "has_type"

AND

`links`.`target_id` = $locationTypeEntityId

QUERY;

	$result = doQuery($query);
	return $result['rows'];
}

function doQuery ($query)
{
	$url = "http://mapping.stanford.edu/db/do-query.php";
	$auth_key = 'c0d068f33165b831195e25550a6d1e76';
	$db = isset($_GET['db']) ? $_GET['db'] : 'mrofl';
	$query = urlencode($query);
	$data = file_get_contents("$url?query=$query&auth_key=$auth_key&db=$db");
	return json_decode( $data, true );
}