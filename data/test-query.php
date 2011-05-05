<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = "http://mapping.stanford.edu/rplviz/data/do-query.php";
$response = array();

$START = time();
$query = buildQuery();
$response = doQuery($query);

echo "query: " . (time() - $START) . " second(s)!\n<br/>";
print_r($response);

function buildQuery()
{
	$query = <<<QUERY

SELECT distinct links.link_type from links

QUERY;

	return $query;
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