Primeiro passo foi criar o projeto laravel com o seguinte comando:

```jsx
composer global require laravel/installer
laravel new example-app
```

segundo passo foi instalar o pacote jetstream

```jsx
composer require laravel/jetstream
```

Logo em seguida foi necessário criar a base de dados em nosso servidor local, foi criado com o nome de progest2. Assim mudamos também o nome da tabela no .env de laravel para progest2. E em seguida rodamos o comando:

```jsx
php artisan migrate
```

depois é necessário instalar o liveware

```jsx
php artisan jetstream:install livewire
```

logo em seguida rodar os seguintes comandos, sendo necessário o node instalado no ambiente de desenvolvimento:
