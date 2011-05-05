<?php
$html = true;

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('memory_limit', '256M');

$url = "http://mapping.stanford.edu/cartogram/do-query.php";

$letters = array();
$lettersWithoutDate = array();
$lettersByAuthor = array();
$lettersByRecipient = array();
$letterCorrespondents = array();

$minYear = 9999;
$maxYear = 0;

$rows = getAllLetters();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$letters[$uid] = $letter = $row;
	$letters[$uid]['srcDate'] = getDateRange($row['srcDate']);
	$letterCorrespondents[$uid] = array('author' => $letter['author'], 'recipient' => $letter['recipient']);
	
	$year = count($letters[$uid]['srcDate']) > 0 ? $letters[$uid]['srcDate'][0] : 0;
	if($year == 0)
	{
		$lettersWithoutDate[] = $uid;
		continue;
	}
	
	/////// BY AUTHOR
	$author = $letter['author'];
	if(!isset($lettersByAuthor[$author]))
	{
		$lettersByAuthor[$author] = array();
	}
	if(!isset($lettersByAuthor[$author][$year]))
	{
	 	$lettersByAuthor[$author][$year] = array();
	}
	$lettersByAuthor[$author][$year][] = $uid;
	
	
	/////// BY RECIPIENT
	$recipient = $letter['recipient'];
	if(!isset($lettersByRecipient[$recipient]))
	{
		$lettersByRecipient[$recipient] = array();
	}
	if(!isset($lettersByRecipient[$recipient][$year]))
	{
		$lettersByRecipient[$recipient][$year] = array();
	}
	$lettersByRecipient[$recipient][$year][] = $uid;
}

$response = array('lettersByAuthor' => $lettersByAuthor,
                  'lettersByRecipient' => $lettersByRecipient,
                  'letterCorrespondents' => $letterCorrespondents
                 );
echo json_encode($response);

function getAllLetters()
{
	$query = <<<QUERY
SELECT

`links`.`entity_id` AS `uid`,
`authors`.`entity_id` AS `author`,
`recipients`.`entity_id` AS `recipient`,
`srcLocs`.`date` AS `srcDate`

FROM `links`

LEFT JOIN `links` AS `authors`
ON `authors`.`target_id` = `links`.`entity_id` AND `authors`.`link_type` = "author_of"

LEFT JOIN `links` AS `recipients`
ON `recipients`.`target_id` = `links`.`entity_id` AND `recipients`.`link_type` = "recipient_of"

LEFT JOIN `links` AS `srcLocs`
ON `srcLocs`.`entity_id` = `links`.`entity_id` AND `srcLocs`.`link_type` = "sent_from"

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