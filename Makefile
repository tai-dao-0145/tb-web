all: docker-dev
.PHONY: all

docker-dev:
	cp .env.dev .env
	make build-docker

docker-stg:
	cp .env.stg .env
	make build-docker

docker-prod:
	cp .env.prod .env
	make build-docker

build-docker:
	cd src && cp .env.example .env
	docker compose up -d --build
	docker compose exec tb-app composer install
	docker compose exec tb-app php artisan key:generate
	docker compose exec tb-app php artisan migrate
	docker compose exec tb-app chown www-data:www-data -R storage bootstrap/cache

local:
	cd src && cp .env.example .env
	cd src && composer install
	cd src && php artisan key:generate
	cd src && php artisan migrate
	cd src && php chown www-data:www-data -R storage bootstrap/cache

deploy:
	echo "🔨 Building Docker image..."
	docker build -f deploy/Dockerfile -t backend:latest .
	docker tag backend:latest asia-northeast1-docker.pkg.dev/tb-develop/backend-app-image/backend:latest
	echo "🔨 Building Docker image success"
	echo "🚀 Deploying to Cloud Run..."
	docker push asia-northeast1-docker.pkg.dev/tb-develop/backend-app-image/backend:latest
	gcloud run deploy backend --image asia-northeast1-docker.pkg.dev/tb-develop/backend-app-image/backend:latest --platform managed --region asia-northeast1 --allow-unauthenticated
	echo "🚀 Deploying to Cloud Run success"

.PHONY: docker
