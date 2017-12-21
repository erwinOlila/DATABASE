<?php
require("sql_connect.php");
$db = $sql;

$args = count($argv);
$today = date("Y-m-d");
$time = date("H:i:s");
$room = $argv[1];  //LB343
$teacher = $argv[2];
$room = substr($room, 2); //343
$cluster = intval($room) > 345 ? 2 : 1;

//GET TEACHER
$sql = mysqli_query($db, "SELECT * FROM teachers WHERE id_number = '{$teacher}'");
if(mysqli_num_rows($sql) == 0){
	echo "No Teacher With ID: '{$teacher}' Found!";
	shell_exec("mosquitto_pub -t 'bunzel/{$cluster}/control' -m 'LB{$room}-0'");
	$sql = mysqli_query($db, "UPDATE room_log SET time_end = '{$time}' WHERE date = '{$today}' AND room = {$room} AND time_end IS NULL");
	exit();
}

$teach = mysqli_fetch_assoc($sql);
$teacher = $teach['indx'];

//CHECK FOR RESERVATIONS
$sql = mysqli_query($db, "	SELECT *
							FROM reservations
							WHERE date = '{$today}'
							AND person_id = {$teacher}
							AND room = {$room}
							AND '{$time}' BETWEEN time_start AND time_end
					");

if(mysqli_num_rows($sql) > 0){
	// echo "Reservation confirmed!";
	echo "RESERVATION - LB".$room."-1".PHP_EOL;
	shell_exec("mosquitto_pub -t 'bunzel/{$cluster}/control' -m 'LB{$room}-1'");
	exit();
}

//CHECK FOR HOLIDAYS
$daymonth = date("1970-m-d");
$sql = mysqli_query($db, "	SELECT * FROM holiday WHERE date = '{$daymonth}'");

if(mysqli_num_rows($sql) > 0){
	$holiday = mysqli_fetch_assoc($sql);
	// echo "Today is {$holiday['name']}! No Classes";
	echo "HOLIDAY - LB".$room."-0".PHP_EOL;
	shell_exec("mosquitto_pub -t 'bunzel/{$cluster}/control' -m 'LB{$room}-0'");
	exit();
}

//CHECK SCHEDULE
$dayname = date("l");
$sql = mysqli_query($db, "	SELECT * FROM room_schedule
							WHERE room = {$room}
							AND teacher = {$teacher}
							AND '{$time}' BETWEEN time_start AND time_end
							AND days LIKE '%{$dayname}%'
						");
$login = 0;
if(mysqli_num_rows($sql) > 0){
	// echo "SCHEDULE - Room schedule confirmed!";
	$login = 1;
}

if($login == 1){
	//CHECK LOGS
	$sql = mysqli_query($db, "	SELECT * FROM room_log
								WHERE room = {$room}
								AND date = '{$today}'
								AND teacher_id = {$teacher}
								AND time_start < '{$time}'
								AND time_end IS NULL
							");

	if(mysqli_num_rows($sql) > 0){
		// echo "Room currently occupied!";
		echo "OCCUPIED - LB".$room."-1".PHP_EOL;
		shell_exec("mosquitto_pub -t 'bunzel/{$cluster}/control' -m 'LB{$room}-1'");
		exit();
	}else{
		$login = 2;
	}
}

if($login == 2){
	$sql = mysqli_query($db, "	INSERT INTO room_log
								VALUES (NULL,
										{$room},
										'{$today}',
										{$teacher},
										NOW(),
										NOW(),
										NULL)");
	// echo PHP_EOL."Room now logged!";
	echo "ALLOW-LB".$room."-1".PHP_EOL;
	shell_exec("mosquitto_pub -t 'bunzel/{$cluster}/control' -m 'LB{$room}-1'");
	exit();
}

shell_exec("mosquitto_pub -t 'bunzel/{$cluster}/control' -m 'LB{$room}-0'");
?>
