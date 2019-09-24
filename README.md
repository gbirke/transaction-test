# Make Long-Running export with intermittent updates safe

This repo shows some examples of the problem described in 
https://stackoverflow.com/q/58066977/130121

To see the queries during the tests, run

	docker-compose exec db tail -f /var/log/mysql/query.log

To see the errors (and success case), run 

	docker-compose run app php /app/exporter.php
	docker-compose run app php /app/exporter_transaction.php
	docker-compose run app php /app/exporter_temporary_table.php



