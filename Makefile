DOCKER_IMAGE=composer-php8.1
USER_ID=$(shell id -u)
GROUP_ID=$(shell id -g)
CURRENT_DIR=$(shell pwd)

.PHONY: build install update build-server-module

build:
	docker build -t $(DOCKER_IMAGE) .

install:
	docker run -u "$(USER_ID):$(GROUP_ID)" -v "$(CURRENT_DIR):/app" -w /app $(DOCKER_IMAGE) composer install

update:
	docker run -u "$(USER_ID):$(GROUP_ID)" -v "$(CURRENT_DIR):/app" -w /app $(DOCKER_IMAGE) composer update

build-server-module:
	docker run -u "$(USER_ID):$(GROUP_ID)" -v "$(CURRENT_DIR):/app" -w /app $(DOCKER_IMAGE) ./bin/katapult build:server-module
