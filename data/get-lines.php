<?php
header("Content-type: text/plain");

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

$lines = array();
$rows = getAllLetters();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	
	$lineId = "l{$row['srcLoc']}_{$row['dstLoc']}";
	if(!isset($lines[$lineId]))
	{
		$lines[$lineId] = array(
			'ecc' => rand(0, 100) / 100 * 0.8 + 0.15,
			'src'  => $row['srcLoc'],
			'dst'  => $row['dstLoc']
		);
	}
}

$response = array('lines'   => $lines);

echo json_encode($response);

function getAllLetters()
{
	$query = <<<QUERY
SELECT

`letters`.`entity_id` AS `uid`,
`srcLocs`.`target_id` AS `srcLoc`,
`dstLocs`.`target_id` AS `dstLoc`

FROM `links` AS `letters`

JOIN `links` AS `srcLocs`
ON `srcLocs`.`entity_id` = `letters`.`entity_id` AND `srcLocs`.`link_type` = "sent_from" AND `srcLocs`.`target_id` IS NOT NULL

JOIN `links` AS `dstLocs`
ON `dstLocs`.`entity_id` = `letters`.`entity_id` AND `dstLocs`.`link_type` = "sent_to" AND `dstLocs`.`target_id` IS NOT NULL

WHERE

`letters`.`link_type` = "has_type"

AND

`letters`.`target_id` = 4

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

function getDateRange( $dateString )
{
	if(!$dateString || empty($dateString)) return array();
	$dateRange = array();
	
	$dates = explode( ',', $dateString );
	for( $i = 0; $i < count($dates); ++$i )
	{
		if(empty($dates[$i])) continue;
		$test = explode('-', $dates[$i]);
		list($year) = explode( '-', $dates[$i] );
		$year = intval($year);
		if($year == 0 || $year == 1) return array();
		$dateRange[] = $year;
	}
	return $dateRange;
}