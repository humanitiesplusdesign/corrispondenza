<?php
$server = '';
$user = '';
$pass = '';

$conn = mysql_connect( $server, $user, $pass );
if( !$conn )
{
	die( "Unable to connect to server: " . mysql_error() );
}

if( !mysql_select_db( '' ) )
{
	die( "Unable to connect to DB:" . mysql_error() );
}

$query = $_GET['query'];
if( stripos($query, "insert") || stripos($query, "delete") || stripos($query, "update") || stripos($query, "drop") )
{
	$response['error'] = "Operation not allowed.";
	echo json_encode( $response );
	exit();
}

$result = mysql_query($query, $conn);

if( !$result )
{
	$message = "Query failed: " . mysql_error() . "\n";
	$message .= "Query: " . $query;
	die( $message );
}

$rows = array();
while( $row = mysql_fetch_assoc($result) )
{
	$rows[] = $row;
}

mysql_close( $conn );

$response = array();
$response['rows'] = $rows;
echo json_encode( $response );