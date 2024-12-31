## How to Install

step 1 install vendor

```bash
composer install
```

step 2 setup database

```bash
php artisan migrate
```

step 3 setup admin

```bash
php artisan db:seed
```

step 4 setup shield

```bash
php artisan shield:setup 
```

```bash
php artisan shield:install
```

step 5 setup storage

```bash
php artisan storage:link
```

step 6 setup key

```bash
php artisan key:generate
```
