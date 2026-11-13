# Campus Lost & Found frontend

This Next.js app is the public and authenticated interface for Campus Lost & Found. It provides the search board, category filters, saved-item actions, sign-in, and the report-item form.

## Run locally

```bash
npm install
cp .env.example .env.local
npm run dev
```

Set `NEXT_PUBLIC_API_URL` to the URL where the Laravel backend is running. The default is `http://localhost:8000`.

## Main API calls

- `GET /api/items` loads the live board.
- `POST /api/auth/login` starts the session.
- `GET /api/auth/user` restores the signed-in user.
- `POST /api/items` creates an authenticated item report.
- `POST` or `DELETE /api/items/{id}/save` updates saved items.
