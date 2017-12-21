<?php
require("sql_connect.php");

//LB3431LI11AC12LI12AC1

$args = count($argv);
if($args < 2){
    exit();
}

$raw = $argv[1];

$room = substr($raw, 2, 3);
$li1 = substr($raw,5,4);
$ac1 = substr($raw,9,4);
$li2 = substr($raw,13,4);
$ac2 = substr($raw,17,4);


for($i = 5; $i <= strlen($raw) - 4; $i += 4){
	$nodes[] = strtolower(substr($raw,$i,3));
	$values[] = substr($raw,$i+3,1);
}

$query = "INSERT INTO node_status (room, moment, ".join(", ",$nodes).") VALUES ({$room}, NOW(), ".join(", ",$values).")";

$result = mysqli_query($sql, $query);

if(!$result){
    echo "Error! ".mysqli_error($sql);
    exit(); 
}
?>
