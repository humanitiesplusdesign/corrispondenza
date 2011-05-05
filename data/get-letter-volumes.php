<?php
$html = true;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = "http://mapping.stanford.edu/cartogram/do-query.php";

$volumes = array();
$incompletes = array();
$minYear = 9999;
$maxYear = 0;
$maxLetters = 0;

$rows = getAllLetters();
$temp = array();
$temp2 = array();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$row['srcDate'] = getDateRange($row['srcDate']);
	
	$year = intval(count($row['srcDate']) > 0 ? $row['srcDate'][0] : null);
	if(!$year || $year == 0) continue;
	$minYear = min($minYear, $year);
	$maxYear = max($maxYear, $year);
	
	if(!$row['srcLoc'] || !$row['dstLoc'])
	{
		if(!isset($temp2[$year]))
		{
			$temp2[$year] = 0;
		}
		++$temp2[$year];
	}
	else
	{
		if(!isset($temp[$year]))
		{
			$temp[$year] = 0;
		}
		++$temp[$year];
	}
}

for($i = $minYear; $i <= $maxYear; ++$i)
{
	$volumes[] = isset($temp[$i]) ? $temp[$i] : 0;
	$incompletes[] = isset($temp2[$i]) ? $temp2[$i] : 0;
	$maxLetters = max($maxLetters, $volumes[$i - $minYear], $incompletes[$i - $minYear]);
}

$response = array();
$response['volumes'] = $volumes;
$response['incompletes'] = $incompletes;
$response['minYear'] = $minYear;
$response['maxYear'] = $maxYear + 1;
$response['maxLetters'] = $maxLetters;
echo json_encode($response);

function getAllLetters()
{
	$query = <<<QUERY
SELECT

`links`.`entity_id` AS `uid`,
`srcLocs`.`target_id` AS `srcLoc`,
`srcLocs`.`date` AS `srcDate`,
`dstLocs`.`target_id` AS `dstLoc`

FROM `links`

LEFT JOIN `links` AS `srcLocs`
ON `srcLocs`.`entity_id` = `links`.`entity_id` AND `srcLocs`.`link_type` = "sent_from"

LEFT JOIN `links` AS `dstLocs`
ON `dstLocs`.`entity_id` = `links`.`entity_id` AND `dstLocs`.`link_type` = "sent_to"

WHERE

`links`.`link_type` = "has_type"

AND `links`.`target_id` = 4

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