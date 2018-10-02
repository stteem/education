<?php 

//No need to continue
if (! isset($_REQUEST['term'])) die('Missing required parameter');

//Lets not start a session unless we already have one
if (! isset($_COOKIE[session_name()])) {
	die("Must be logged in");
}
error_log("Checking session =". $_COOKIE[session_name()]);

session_start();

if (! isset($_SESSION['user_id'])) {
	die('ACCESS DENIED');
}

//Don't even make a database connection until we are happy
require_once "pdo.php";

header("Content-type: application/json; charset=utf-8");

$term = $_REQUEST['term'];

error_log("Looking up typehead term =" . $term);

$stmt = $pdo->prepare('SELECT name FROM institution WHERE name LIKE :prefix');
$stmt->execute(array(':prefix' => $term."%"));

$retval = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$retval[] = $row['name'];
}

echo(json_encode($retval, JSON_PRETTY_PRINT));