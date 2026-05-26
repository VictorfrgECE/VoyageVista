# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

VoyageVista is a travel planning web app. Users can browse destinations, compare transports and accommodations, build itineraries, and make reservations.

**Stack:** React + Vite (frontend) · PHP 8.1+ REST API (backend) · MySQL (database)

## Commands

### Frontend
```bash
cd frontend
npm install          # first time
npm run dev          # dev server → http://localhost:3000
npm run build        # production build → dist/
npm run lint         # ESLint
```

### Backend
```bash
# Serve with PHP's built-in server (dev only)
cd backend
php -S localhost:8000
```

### Database
```bash
# Créer la base et charger le schéma
mysql -u root -p < database/init.sql

# Charger les données de test (après init.sql)
mysql -u root -p voyagevista < database/seed.sql
```
Mot de passe de tous les comptes seed : `Test1234!`

Copy `backend/.env.example` to `backend/.env` and set your DB credentials before running the backend.

## Architecture

### Frontend (`frontend/src/`)
- **`services/api.js`** — Axios instance pointed at `/api`. Attaches Bearer token from `localStorage` on every request; redirects to `/login` on 401.
- **`App.jsx`** — React Router root. All routes are declared here.
- **`pages/`** — One file per route. Pages call `api.js` directly; no global state manager yet.
- **`components/`** — Shared UI (currently just `Navbar`). CSS Modules for scoping.
- Vite proxies `/api` → `http://localhost:8000` in dev, so no CORS issues during development.

### Backend (`backend/`)
- **`index.php`** — Single entry point. Parses URL segments (`/api/{resource}/{id}`), loads the matching controller, calls `handle($method, $id, $body)`.
- **`config/database.php`** — Singleton PDO connection. Reads credentials from `$_ENV` (populated from `.env`).
- **`controllers/`** — One controller per resource (`UserController`, `DestinationController`, …). Each exposes a `handle(method, id, body)` method that delegates to private CRUD methods.
- Auth is done inside `UserController::handleAuth()`. The token is a simple HMAC-signed base64 payload — swap for a real JWT library before production.
- `.htaccess` rewrites all requests to `index.php`.

### Database (`database/`)
- **`init.sql`** — Schema complet (12 tables). Charger en premier.
- **`seed.sql`** — Données de test réalistes (10 destinations, 5 universités, 10 users). Charger après init.sql.

Rôles utilisateurs : `admin` | `prestataire` | `etudiant` (défaut).

Relations principales :
```
users ──< itineraries ──< reservations
users ──< notifications
destinations ──< transports
destinations ──< accommodations
destinations ──< activities
destinations ──< universities ──< student_housing
destinations ──< budget_estimations   (1-1, UNIQUE)
destinations ──< visa_info            (1 par zone : EU / non-EU)
student_housing >── universities      (nullable)
```
`reservations.type` + `reservations.reference_id` = référence polymorphe vers transport / accommodation / activity.
`budget_estimations.monthly_total_avg` est une colonne générée (STORED) — ne pas l'insérer manuellement.
