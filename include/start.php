<?php

session_start();

header("Content-Type: text/html; charset=utf-8"); // Prevent incorrect encoding in Browser
error_reporting( E_ALL );
function __autoload($class_name) {

		//Dump::object($class_name, __FILE__ . " - " . __LINE__ . " - " . " c class_name");
		if($_SERVER['DOCUMENT_ROOT'] == '')
			$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
		require_once $_SERVER['DOCUMENT_ROOT'] . '/class/class.' . strtolower($class_name) . '.php';
}

Database::connectMysql();



?>