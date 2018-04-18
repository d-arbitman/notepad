<?php
require_once("class.pgsql.php");
require_once("functions.php");
function GetGlobalConnectionOptions() {
	return
		array(
			'server' => 'localhost',
			'port' => '5432',
			'username' => '***fill in values here***',
			'password' => '***fill in values here***',
			'database' => '***fill in values here***',
			'client_encoding' => 'utf8'
	  );
}

/*
 *    returns new PGSQL object with open DB connection
 *    */
function getDBConnection () {
  $dbConnSettings = GetGlobalConnectionOptions ();
	return new pgsql ($dbConnSettings['server'], $dbConnSettings['port'], $dbConnSettings['database'], $dbConnSettings['username'], $dbConnSettings['password']);
}

$db = getDBConnection();
