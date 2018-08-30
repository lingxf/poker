<?php
$debug = false;
if(gethostname() == 'linux-bug')
	$debug = true;
if(!$debug){
	$server = 'sqld.duapp.com:4050';
	$user = 'ff5c83e1a18c4d29a16c3d2d4a9aa0dd';
	$pass = '82d93a5d2d6942f79310b324eb2c617a';
	$dbname = 'VVcrAlQxdnFcXqPksWts';
	$dbname = 'UsNSlkWpwXhVoOpylKgC';
}else{
	$server = 'cedump-sh.ap.qualcomm.com';
	$server = 'linux-bug.ap.qualcomm.com';
	$server = 'localhost';
	$user = 'card';
	$pass = 'card2pass';
	$dbname = 'poker';
	/*
	$user = 'mdb';
	$pass = 'mdb2pass';
	*/
}

$link=mysql_connect($server, $user, $pass);
mysql_query("set character set 'utf8'");//..
mysql_query("set names 'utf8'");//.. 
$dbc=mysql_select_db($dbname,$link);
?>
