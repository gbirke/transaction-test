<?php

$dsn = 'mysql:host=db;dbname=my_test';
$pdo = new PDO($dsn, 'test', 'test', [
	PDO::ATTR_TIMEOUT => 5,
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

set_time_limit( 3 );

$pdo->query('UPDATE donations SET status="PAID" WHERE status="NEW"');
