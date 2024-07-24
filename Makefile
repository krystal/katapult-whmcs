.PHONY: coverage

coverage:
	XDEBUG_MODE=coverage \
	./vendor/bin/phpunit \
	--testdox \
	--display-warnings \
	--coverage-html=tests/report \
	--coverage-text
