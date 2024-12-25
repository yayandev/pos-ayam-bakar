## How to Install

step 1 install vendor

```bash
composer install
```

step 2 setup database

```bash
php artisan migrate
```

step 3 setup filament super admin

```bash
php artisan filament:super-admin
```

step 4 setup shield

```bash
php artisan shield:setup && php artisan shield:install
```

step 5 setup storage

```bash
php artisan storage:link
```

step 6 setup key

```bash
php artisan key:generate
```
