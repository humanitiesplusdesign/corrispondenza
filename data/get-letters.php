<?php
header("Content-type: text/plain");

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

$letters = array();
$lettersByYear = array();
$lettersWithoutDate = array();
$lines = array();
$lineEccentricity = array();
$persistentLines = array();

$minYear = 9999;
$maxYear = 0;
$maxDocs = 0;

$rows = getAllLetters();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$letters[$uid] = $letter = $row;
	$letters[$uid]['srcDate'] = getDateRange($row['srcDate']);
	
	$year = count($letters[$uid]['srcDate']) > 0 ? $letters[$uid]['srcDate'][0] : 0;
	if($year == 0)
	{
		$lettersWithoutDate[] = $uid;
		if($row['srcLoc'] && $row['dstLoc'])
		{
			$lineId = "l{$row['srcLoc']}_{$row['dstLoc']}";
			if(!isset($persistentLines[$lineId]))
			{
				$persistentLines[$lineId] = array(
					'size'    => 0,
					'src'     => $row['srcLoc'],
					'dst'     => $row['dstLoc'],
				    'letters' => array()
				);
			}
			++$persistentLines[$lineId]['size'];
			$persistentLines[$lineId]['letters'][] = $uid;
	
			if(!isset($lineEccentricity[$lineId]))
			{
				$lineEccentricity[$lineId] = rand(0.05, 0.95);
			}
		}
		continue;
	}
	
	$minYear = min($minYear, $year);
	$maxYear = max($maxYear, $year);
	
	if(!$row['srcLoc'] || !$row['dstLoc']) continue;
	
	$lineId = "l{$row['srcLoc']}_{$row['dstLoc']}";
	if(!isset($lines[$lineId]))
	{
		$lines[$lineId] = array(
			'size' => 0,
			'src'  => $row['srcLoc'],
			'dst'  => $row['dstLoc'],
		    'letters' => array()
		);
	}
	
	if(!isset($lineEccentricity[$lineId]))
	{
		$lineEccentricity[$lineId] = rand(0.05, 0.95);
	}
	
	if(!isset($lettersByYear[$year]))
	{
		$lettersByYear[$year] = array();
	}
	$lettersByYear[$year][] = $uid;
	$maxDocs = max($maxDocs, count($lettersByYear[$year]));
}

$response = array('letters' => $letters,
                  'lettersByYear' => $lettersByYear,
				  'lettersWithoutDate' => $lettersWithoutDate,
                  'minYear' => $minYear,
                  'maxYear' => $maxYear,
				  'maxDocs' => $maxDocs,
                  'lines'   => $lines,
                  'persistentLines' => $persistentLines,
                  'lineEccentricity' => $lineEccentricity
                 );
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