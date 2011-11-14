<?php
header("Content-type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
set_time_limit(60);

ProcessRequest();

function ApiFindAuthorNames()
{
	$snippet = GetRequiredQueryParameter('snippet');
	
	$action = "query";
	$collection = "MPerson";
	$limit = 5;
	$filter = array('_id' => true, 'NameRaw' => true);
	$personRegex = array('$regex' => ".*$snippet.*", '$options' => 'i');
	$query = array('NameRaw' => $personRegex);
	
	$result = doQuery($action, $collection, $query, $limit, $filter);
	$response = array('people' => $result['result']);
	Respond($response);
}

function ApiGetLocations()
{
	$locations = array();
	$dots = array();
	
	$action = "query";
	$collection = "MPlace";
	$limit = null;
	$filter = array(
		'_id' => true,
		'lon' => true,
		'lat' => true,
		'FullName' => true,
	);
	
	$existsQuery = array('$exists' => true);
	
	$query = array('Coords' => $existsQuery);
	
	$results = doQuery($action, $collection, $query, $limit, $filter);
	$results = $results['result'];
	
	for($i = 0; $i < count($results); ++$i)
	{
		$location = $results[$i];
		$uid = $location['_id'];
		
		$location['lon'] = floatval($location['lon']);
		$location['lat'] = floatval($location['lat']);
		
		$dots[$uid] = array(
			'visible' => false,
			'lon'     => $location['lon'],
			'lat'     => $location['lat'],
			'name'    => $location['FullName'],
		);
		
		$locations[$uid] = $location;
	}
	
	$response = array(
		'locations' => $locations,
		'dots' => $dots,
	);
	Respond($response);
}

function ApiGetMapData()
{
	$name = GetRequiredQueryParameter('name');
	$name = str_replace(' ', '+', $name);
	
	$minYear = 9999;
	$maxYear = 0;
	$volumes = array();
	$maxLetters = 0;
	
	$action = "query";
	$collection = "Letter";
	$limit = null;
	$filter = array(
		'_id' => true,
		'Title' => true,
		'AuthorMPerson' => true,
		'RecipientMPerson' => true,
		'Date' => true,
		'SourceMPlace' => true,
		'DestinationMPlace' => true,
	);
	
	$personRegex = array('$regex' => ".*$name.*", '$options' => 'i');
	$authorQuery = array('Author' => $personRegex);
	$authorRawQuery = array('AuthorRaw' => $personRegex);
	$recipientQuery = array('Recipient' => $personRegex);
	$recipientRawQuery = array('RecipientRaw' => $personRegex);
	$nameQuery = array($authorQuery, $authorRawQuery, $recipientQuery, $recipientRawQuery);
	
	$existsQuery = array('$exists' => true);
	
	$query = array(
		'$or' => $nameQuery,
		'SourceMPlace' => $existsQuery,
		'DestinationMPlace' => $existsQuery
	);
	
	$letters = array();
	$undatedLetters = array();
	$result = doQuery($action, $collection, $query, $limit, $filter);
	$results = $result['result'];
	for($i = 0; $i < count($results); ++$i)
	{
		$letter = $results[$i];
		$letterYear = null;
		if(isset($letter['Date']))
		{
			$letterDate = $letter['Date'];
			if(isset($letterDate['year']) && $letterDate['year'] != null)
			{
				$letterYear = intval($letterDate['year']);
				if($letterYear > 1)
				{
					$letters[$letterYear][] = $letter;
					
					$maxYear = max($maxYear, $letterYear);
					$minYear = min($minYear, $letterYear);
					
					if(!isset($volumes[$letterYear]))
					{
						$volumes[$letterYear] = 0;
					}
					++$volumes[$letterYear];
					
					$maxLetters = max($maxLetters, $volumes[$letterYear]);
				}
				else
				{
					$undatedLetters = $letter['_id'];
				}
			}
		}
		else
		{
			$undatedLetters[] = $letter['_id'];
		}
	}
	$response = array(
		'minYear' => $minYear,
		'maxYear' => $maxYear + 1,
		'maxLetters' => $maxLetters,
		'maxPlottableLetters' => $maxLetters,
		'volumes' => $volumes,
		'incompletes' => array(),
		'undatedLetters' => $undatedLetters,
		'letters' => $letters);
	Respond($response);
}

function DoQuery ($action, $collection, $query, $limit, $filter)
{
	$url = "http://qlibrium:K4ohen@mapping.stanford.edu/data/api.py";
	$queryString = "?action=$action";
	
	$query = json_encode($query);
	$queryString .= "&q=$query";
	
	if( $limit != null ) $queryString .= "&limit=$limit";
	if( $collection != null ) $queryString .= "&collection=$collection";
	
	if( $filter != null )
	{
		$filter = json_encode($filter);
		$queryString .= "&filter=$filter";
	}
	
	$url .= $queryString;
	$data = file_get_contents($url);
	return json_decode( $data, true );
}

function GetRequiredQueryParameter($parameterName)
{
	if(!isset($_GET[$parameterName]) || !($parameterValue = $_GET[$parameterName]))
	{
		SendBadRequest("Required parameter '$parameterName' not specified.");
	}
	
	return $parameterValue;
}

function ProcessRequest()
{
	$action = GetRequiredQueryParameter('action');

	$action = 'Api' . ToCamelCase($action);
	if(function_exists($action))
	{
		$action();
	}
}

function Respond($response, $isJson = true)
{
	if($isJson)
	{
		$response = json_encode($response);
	}

	echo $response;
	exit();
}

function SendBadRequest($message)
{
	SetStatus($message, 400);
	$response = array('error' => $message);
	Respond($response);
}

function SetStatus($message, $statusCode)
{
	header($message, true, $statusCode);
}

function ToCamelCase($action)
{
	$words = explode('-', $action);
	for($i = 0; $i < count($words); ++$i)
	{
		$word = $words[$i];
		$words[$i] = ucwords(strtolower($word));
	}
	
	return implode('', $words);
}