<?php
defined('ENTITY_PERSON') || define('ENTITY_PERSON', 3);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = "http://mapping.stanford.edu/cartogram/do-query.php";

$people = array();

$rows = getAllPeople();
for($i = 0; $i < count($rows); ++$i)
{
	$row = $rows[$i];
	$uid = $row['uid'];
	$people[$uid] = $row;
}

$response = array();
$response['people'] = $people;
echo json_encode($response);

function getAllPeople()
{
	$personEntityTypeId = ENTITY_PERSON;
	$query = <<<QUERY
SELECT

`links`.`entity_id` AS `uid`,
`entities`.`name_vern` AS `name`

FROM `links`

LEFT JOIN `entities`
ON `entities`.`uid` = `links`.`entity_id`

WHERE

`links`.`link_type` = "has_type"

AND

`links`.`target_id` = $personEntityTypeId

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