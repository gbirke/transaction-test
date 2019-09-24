<?php
/* This is the failing exporter example where the external process is blocked and does not update */

$dsn = 'mysql:host=db;dbname=my_test';
$pdo = new PDO($dsn, 'test', 'test');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$initStatements = [
	'CREATE TABLE IF NOT EXISTS donations (id smallint unsigned PRIMARY KEY NOT NULL auto_increment, status VARCHAR(10), INDEX status_idx (status))  ENGINE = InnoDB',
	'DELETE FROM donations',
	'INSERT INTO donations(status) VALUES' . implode(',', array_fill(0,3,'("NEW")')),
	'INSERT INTO donations(status) VALUES' . implode(',', array_fill(0,3,'("PAID")')),
];
foreach($initStatements as $stmt) {
	$pdo->query($stmt);
}

$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
$pdo->beginTransaction();
$result = $pdo->query('SELECT * FROM donations WHERE status="PAID" FOR UPDATE');
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
$pdo->query('UPDATE donations SET status="EXPORTED" WHERE status="PAID"');
$pdo->commit();

$expected = ['EXPORTED', 'EXPORTED', 'EXPORTED', 'PAID', 'PAID', 'PAID', ];
$actual = $pdo->query("SELECT status FROM donations")->fetchAll(PDO::FETCH_COLUMN);
if ( array_diff($expected, $actual) === [] ) {
	echo "Export successful\n";
} else {
	echo "Export failed!\n";
	echo "Expected:\n";
	echo implode('', array_map('formatTable', $expected));
	echo "Actual:\n";
	echo implode('', array_map('formatTable', $actual));
}

function formatTable( $row) {
	return sprintf( "| %10s |\n", $row );
}
