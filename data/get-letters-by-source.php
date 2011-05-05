<?php
$html = true;

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('memory_limit', '256M');

$url = "http://mapping.stanford.edu/cartogram/do-query.php";

$letterSources = array();

$rows = getLetterSources();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$letterSources[$uid] = $row;
}

$response = array('letterSources' => $letterSources
                 );
echo json_encode($response);

function getLetterSources()
{
	$query = <<<QUERY

SELECT

`links`.`entity_id` AS `uid`,
`sources`.`target_id` AS `source`

FROM `links`

LEFT JOIN `links` AS `sources`
ON `sources`.`entity_id` = `links`.`entity_id` AND `sources`.`link_type` = "has_source"

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