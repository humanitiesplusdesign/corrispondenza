<?php
$html = true;

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('memory_limit', '256M');

$url = "http://mapping.stanford.edu/cartogram/do-query.php";

$letterUrls = array();

$rows = getAllLetters();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$letter = $row;
	$letterUrls[$uid] = $letter['url'];
}

$response = array('letterUrls' => $letterUrls);
echo json_encode($response);

function getAllLetters()
{
	$query = <<<QUERY
SELECT

`links`.`entity_id` AS `uid`,
`urls`.`data` AS `url`

FROM `links`

JOIN `links` AS `urls`
ON `urls`.`entity_id` = `links`.`entity_id` AND `urls`.`link_type` = "has_URI"

WHERE

`links`.`link_type` = "has_type"

AND

`links`.`target_id` = 4

QUERY;

	$result = doQuery($query);
	return $result['rows'];
}

function doQuery ($query)
{
	global $url;
	$query = str_replace( "\n", " ", $query );
	$query = urlencode($query);
	$data = file_get_contents("$url?query=$query");
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