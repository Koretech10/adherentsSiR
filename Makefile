start:
	symfony server:start -d

stop:
	symfony server:stop

phpstan:
	symfony php ./vendor/bin/phpstan analyse -c config/checkers/phpstan.neon