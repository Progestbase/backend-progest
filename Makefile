# ==========================================
# Makefile para Backend Progest (Laravel)
# ==========================================

# O alvo 'deploy' automatiza o processo de atualização e reinicialização do ambiente Docker.
deploy: docker-down docker-up cache-clear migrate

# Passo 1: Derruba os contêineres existentes
docker-down:
	@echo "--- 1/3: Derrubando os contêineres Docker Compose atuais..."
	docker-compose down

# Passo 2: Constrói as imagens e levanta os serviços
docker-up:
	@echo "--- 2/3: Construindo e subindo os serviços Docker Compose..."
	docker-compose up -d --build

# Passo 3: Roda as migrations do Laravel
migrate:
	@echo "--- 3/3: Executando migrations do Laravel..."
	@sleep 10
	docker-compose exec -T progest-api php artisan migrate --force

# Primeiro deploy (inclui criação do .env)
first-deploy: docker-down docker-up migrate-fresh seed

# Roda migrations do zero (CUIDADO: apaga dados)
migrate-fresh:
	@echo "--- Executando migrate:fresh..."
	@sleep 10
	docker-compose exec -T progest-api php artisan migrate:fresh --force

# Roda seeders
seed:
	@echo "--- Executando db:seed..."
	docker-compose exec -T progest-api php artisan db:seed --force

# Limpa cache do Laravel
cache-clear:
	@echo "--- Limpando cache do Laravel..."
	docker-compose exec -T progest-api php artisan cache:clear
	docker-compose exec -T progest-api php artisan config:clear
	docker-compose exec -T progest-api php artisan route:clear
	docker-compose exec -T progest-api php artisan view:clear

# Otimiza para produção
optimize:
	@echo "--- Otimizando Laravel para produção..."
	docker-compose exec -T progest-api php artisan config:cache
	docker-compose exec -T progest-api php artisan route:cache
	docker-compose exec -T progest-api php artisan view:cache

# Alvo opcional para limpar completamente o ambiente
clean:
	@echo "--- Limpando contêineres, imagens e volumes..."
	docker-compose down -v --rmi all

# Logs do container
logs:
	docker-compose logs -f progest-api

# Alvo de ajuda
help:
	@echo "Comandos disponíveis (make):"
	@echo "  make deploy       : Derruba, sobe containers e roda migrations"
	@echo "  make first-deploy : Primeiro deploy com migrate:fresh e seed"
	@echo "  make migrate      : Apenas roda migrations"
	@echo "  make seed         : Apenas roda seeders"
	@echo "  make cache-clear  : Limpa todos os caches do Laravel"
	@echo "  make optimize     : Otimiza Laravel para produção"
	@echo "  make clean        : Remove tudo (containers, imagens, volumes)"
	@echo "  make logs         : Mostra logs do container API"
	@echo "  make help         : Mostra esta mensagem"

.PHONY: deploy docker-down docker-up migrate first-deploy migrate-fresh seed cache-clear optimize clean logs help
