# Campus Lost & Found backend

This Laravel application provides the session-authenticated API for Campus Lost & Found. It owns users, lost-and-found listings, saved items, validation, and seeded development data.

## Run locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The API is available at `http://localhost:8000/api`.

## Development account

The seed command creates `demo@campus.edu` with the password `password`. Change or remove this account before using a deployed environment.

## API surface

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/user`
- `POST /api/auth/logout`
- `GET /api/items?q=&category=`
- `POST /api/items` for authenticated reports
- `GET /api/saved-items` for authenticated saved items
- `POST /api/items/{id}/save`
- `DELETE /api/items/{id}/save`
