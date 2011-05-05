<?php
header("Content-type: text/plain");

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

$name = isset($_GET['name']) ? $_GET['name'] : null;
if($name == null) exit();

$letters = array();
$undatedLetters = array();
$volumes = array();
$incompletes = array();

$minYear = 9999;
$maxYear = 0;
$maxLetters = 0;
$maxPlottableLetters = 0;

$rows = getAllLetters($name);
$temp = array();
$temp2 = array();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$letter = $row;
	$letter['srcDate'] = getDateRange($row['srcDate']);
	
	$year = count($letter['srcDate']) > 0 ? intval($letter['srcDate'][0]) : 0;
	$letter['srcDate'] = $year;
	if($year == 0)
	{
		$undatedLetters[] = $uid;
	}
	else
	{
		if(!isset($letters[$year]))
		{
			$letters[$year] = array();
		}
		$letters[$year][] = $uid;
	
		$minYear = min($minYear, $year);
		$maxYear = max($maxYear, $year);
	
		if(!$row['srcLoc'] || !$row['dstLoc'])
		{
			if(!isset($incompletes[$year]))
			{
				$incompletes[$year] = 0;
			}
			++$incompletes[$year];
			$maxLetters = max($maxLetters, isset($volumes[$year]) ? $volumes[$year] : 0, $incompletes[$year]);
//			if(!isset($temp2[$year]))
//			{
//				$temp2[$year] = 0;
//			}
//			++$temp2[$year];
//			continue;
		}
		else
		{
			if(!isset($volumes[$year]))
			{
				$volumes[$year] = 0;
			}
			++$volumes[$year];
			$maxLetters = max($maxLetters, $volumes[$year], isset($incompletes[$year]) ? $incompletes[$year] : 0);
			$maxPlottableLetters = max($maxPlottableLetters, $volumes[$year]);
//			if(!isset($temp[$year]))
//			{
//				$temp[$year] = 0;
//			}
//			++$temp[$year];
		}
	}
}

//for($i = $minYear; $i <= $maxYear; ++$i)
//{
//	$volumes[] = isset($temp[$i]) ? $temp[$i] : 0;
//	$incompletes[] = isset($temp2[$i]) ? $temp2[$i] : 0;
//	$maxLetters = max($maxLetters, $volumes[$i - $minYear], $incompletes[$i - $minYear]);
//	$maxPlottableLetters = max($maxPlottableLetters, $volumes[$i - $minYear]);
//}

$response = array('minYear' => $minYear,
                  'maxYear' => $maxYear + 1,
				  'maxLetters' => $maxLetters,
				  'maxPlottableLetters' => $maxPlottableLetters,
				  'volumes' => $volumes,
				  'incompletes' => $incompletes,
                  'letters' => $letters,
				  'undatedLetters' => $undatedLetters
                 );
echo json_encode($response);

function getAllLetters($name)
{
	$query = <<<QUERY
SELECT

`letters`.`target_id` AS `uid`,
`srcLocs`.`target_id` AS `srcLoc`,
`srcLocs`.`date` AS `srcDate`,
`dstLocs`.`target_id` AS `dstLoc`

FROM `links`

JOIN `entities` AS `people`
ON `people`.`uid` = `links`.`entity_id` AND `people`.`name_vern` LIKE "%$name%"

JOIN `links` AS `letters`
ON `letters`.`entity_id` = `links`.`entity_id` AND (`letters`.`link_type` = "author_of" OR `letters`.`link_type` = "recipient_of")

LEFT JOIN `links` AS `srcLocs`
ON `srcLocs`.`entity_id` = `letters`.`target_id` AND `srcLocs`.`link_type` = "sent_from"

LEFT JOIN `links` AS `dstLocs`
ON `dstLocs`.`entity_id` = `letters`.`target_id` AND `dstLocs`.`link_type` = "sent_to"

WHERE

`links`.`link_type` = "has_type"

AND

`links`.`target_id` = 3

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