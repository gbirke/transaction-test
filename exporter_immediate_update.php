<?php
/* This is the successfule exporter example, using a temporary table */

$dsn = 'mysql:host=db;dbname=my_test';
$pdo = new PDO($dsn, 'test', 'test');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

define('PAID_SAMPE_SIZE', 10000);
define('NEW_SAMPE_SIZE', 10);

$initStatements = [
	'CREATE TABLE IF NOT EXISTS donations (id int unsigned PRIMARY KEY NOT NULL auto_increment, status VARCHAR(10), INDEX status_idx (status))  ENGINE = InnoDB',
	'DELETE FROM donations',
	'INSERT INTO donations(status) VALUES' . implode(',', array_fill(0, NEW_SAMPE_SIZE,'("NEW")')),
	'INSERT INTO donations(status) VALUES' . implode(',', array_fill(0, PAID_SAMPE_SIZE,'("PAID")')),
	'CREATE TEMPORARY TABLE export_ids (id int unsigned PRIMARY KEY) ENGINE = InnoDB', 
];
foreach($initStatements as $stmt) {
	$pdo->query($stmt);
}

$start = microtime(true);
$pdo->query('INSERT INTO export_ids SELECT id from donations where status="PAID"');
$result = $pdo->query('SELECT * FROM donations JOIN export_ids ON donations.id=export_ids.id');
$count = 0;
while( $row = $result->fetch() ) {
	
	// Simulate independent external process
	if ($count === 1) {
		exec( 'php '. __DIR__ .'/payment_handler.php', $output);
		if ( $output ) {
			echo "------ Payment Handler Output ------\n";
			echo implode("\n", $output)."\n";
			echo "------------------------------------\n";
		}
	}
	$count++;

	// Row processing goes here ...
}
$pdo->query('UPDATE donations,export_ids SET status="EXPORTED" WHERE donations.id=export_ids.id');
$elapsedTable = microtime(true) - $start;

foreach($initStatements as $stmt) {
	if (strpos( $stmt, 'TEMPORARY TABLE' ) ) {
		continue;
	}
	$pdo->query($stmt);
}

$start = microtime(true);
$pdo->beginTransaction();
$updateQuery = $pdo->prepare('UPDATE donations SET status="EXPORTED" WHERE id = ?');
$result = $pdo->query('SELECT * FROM donations WHERE status="PAID"');
$count = 0;
while( $row = $result->fetch() ) {
	
	// Simulate independent external process
	if ($count === 1) {
		exec( 'php '. __DIR__ .'/payment_handler.php', $output);
		if ( $output ) {
			echo "------ Payment Handler Output ------\n";
			echo implode("\n", $output)."\n";
			echo "------------------------------------\n";
		}
	}
	$count++;

	$updateQuery->execute([$row['id']]);
}
$pdo->query('UPDATE donations,export_ids SET status="EXPORTED" WHERE donations.id=export_ids.id');
$pdo->commit();
$elapsedSingleUpdate = microtime(true) - $start;

printf("%.3f seconds elapsed for external table\n", $elapsedTable);
printf("%.3f seconds elapsed for single updated\n", $elapsedSingleUpdate);

$expected = array_merge(
	array_fill( 0, PAID_SAMPE_SIZE, 'EXPORTED' ),
	array_fill( 0, NEW_SAMPE_SIZE, 'PAID' )
);
$actual = $pdo->query("SELECT status FROM donations")->fetchAll(PDO::FETCH_COLUMN);
if ( array_diff($expected, $actual) === [] ) {
	echo "Export successful\n";
} else {
	echo "Export failed!\n";
	echo "Expected:\n";
	echo implode('', array_map('formatTable', $expected));
	echo "\nActual:\n";
	echo implode('', array_map('formatTable', $actual));
}

function formatTable( $row) {
	return sprintf( "| %10s |\n", $row );
}

