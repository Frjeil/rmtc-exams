# rmtc-exams

Applicazione API per la gestione di trascrizioni d'esame (AI-Assisted Hiring Test).

- **Backend**: Laravel 13 (PHP 8.5), API-only, autenticazione **Sanctum** (Bearer token)
- **Frontend**: React 19 + TypeScript + Vite + **Mantine** (SPA separata)
- **Database**: PostgreSQL 18
- **Infrastruttura**: Docker Compose — 3 container (`frontend`, `backend`, `db`)

## Avvio rapido

```bash
docker compose up -d --build    # oppure: make up
```

Il codice di backend e frontend è montato via **bind mount**: le modifiche si
vedono subito (hot-reload per il frontend, php-fpm rilegge i file per il backend).
Serve una rebuild solo dopo modifiche ai `Dockerfile`:
`make backend-build` / `make frontend-build`.

| Servizio | URL |
|---|---|
| Frontend (Vite) | http://localhost:5173 |
| Backend (API) | http://localhost:8080/api |
| PostgreSQL | localhost:5432 (db `rmtc`, user `rmtc`, password `rmtc`) |

Le migration girano automaticamente all'avvio del backend. Per un DB pulito con
dati demo: `make artisan cmd='migrate:fresh --seed'`.

### Documentazione API

Swagger UI su **http://localhost:8080/docs** (spec OpenAPI su `/docs/api-docs`),
generata con `l5-swagger` dagli attributi dei controller. Per rigenerarla dopo
modifiche: `make artisan cmd='l5-swagger:generate'`.

### Utenti demo (seed)

| Ruolo | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Supervisor | `supervisor@example.com` | `password` |
| Studente | `student@example.com` | `password` |

## Endpoint

### Pubblici
| Metodo | Path | Descrizione |
|---|---|---|
| POST | `/api/auth/login` | Login, restituisce `token` Bearer (max 5 tentativi/min, oltre → 429) |
| POST | `/api/auth/register` | Registrazione `{name, email, password, password_confirmation}` → token, ruolo `user` |
| GET | `/api/exams` | Elenco esami disponibili. Query: `title` (parziale), `date` (YYYY-MM-DD), `sort=asc\|desc` (per data) |

### Privati (autenticati con `Authorization: Bearer <token>`)
| Metodo | Path | Ruolo | Descrizione |
|---|---|---|---|
| GET | `/api/auth/me` | qualsiasi | Profilo utente |
| POST | `/api/auth/logout` | qualsiasi | Revoca del token |
| POST | `/api/exams/{exam}/enroll` | user | Iscrizione self-service a un esame (dup → 409) |
| GET | `/api/my/exams` | user | I propri esami con voto |
| POST | `/api/admin/exams` | admin | Crea esame `{title, date}`; rifiutato se festivo italiano |
| POST | `/api/supervisor/exams/{exam}/assign` | supervisor | Assegna/aggiorna voto `{user_id, vote}` (richiede iscrizione, 18–30) |
| GET | `/api/exams/{exam}/users` | supervisor | Elenco degli utenti iscritti a un esame |
| GET | `/api/supervisor/my/votes` | supervisor | I voti assegnati dal supervisor corrente. Query: `title` (parziale), `date`, `sort=asc\|desc` (per data esame) |

### Rate limiting e CORS

- Tutte le rotte `/api/*` sono limitate a **60 richieste/min** (per utente o IP);
  `login` e `register` a **5/min**. Oltre il limite → `429`.
- **CORS** configurato per l'origine del frontend (`CORS_ALLOWED_ORIGINS`,
  default `http://localhost:5173`). Con il proxy Vite le richieste sono
  same-origin, quindi il CORS non viene quasi mai esercitato in dev.

## Giorni festivi (Nager.Date)

Quando l'admin crea un esame, il backend verifica via **Nager.Date**
(`GET /date.nager.at/api/v3/PublicHolidays/{year}/IT`, nessuna API key) che la
data non sia un giorno festivo italiano; in tal caso la richiesta è rifiutata
con `422` e un messaggio esplicativo.

- **Cache**: l'elenco dei festivi dell'anno è memorizzato in cache (una chiamata
  HTTP all'anno per anno, non per ogni richiesta). Il valore in cache è un array,
  non un oggetto, per compatibilità con lo store `database`.
- **Timeout**: la chiamata HTTP ha un timeout breve (default 3s).

### Comportamento in caso di errore dell'API esterna (timeout o indisponibilità)

La creazione dell'esame viene **rifiutata con HTTP 503** e un messaggio chiaro
("Servizio dei giorni festivi non disponibile, riprova più tardi"). L'errore è
loggato.

**Perché fail-closed:** se non possiamo verificare che una data non sia festiva,
lasciare creare l'esame introdurrebbe un dato potenzialmente non valido
(un esame in un giorno festivo). È una violazione di integrità del dominio, non
un semplice degrado di disponibilità; quindi l'incertezza del servizio esterno si
traduce in un rifiuto esplicito piuttosto che in un dato potenzialmente errato.

Nota: se la cache per l'anno è già popolata, la verifica usa i dati in cache e
non dipende dalla disponibilità dell'API in quel momento.

## Modello dati

```
users:    id, name, email (unique), password, role (user|admin|supervisor)
exams:    id, title, date
exam_user:user_id, exam_id, vote (nullable), graded_by (nullable), UNIQUE(user_id, exam_id)
```

Un utente non può avere lo stesso esame due volte (vincolo `UNIQUE` a livello DB).
`graded_by` traccia il supervisor che ha assegnato il voto (alimenta la sezione
"Votazioni" del supervisor).

## Test e qualità

```bash
make test         # php artisan test (feature test, DB sqlite in-memory, Http::fake per Nager.Date)
make frontend-test # Vitest + React Testing Library (frontend)
make pint         # Laravel Pint (backend)
make tsc          # type-check TypeScript (frontend)
make oxlint       # lint frontend
```

I test simulano Nager.Date con `Http::fake()` (inclusi timeout e 5xx): nessuna
chiamata HTTP reale durante i test. I test frontend usano `@testing-library/react`
con il modulo API mockato (nessuna richiesta di rete).

## Struttura

```
backend/                 # Laravel 13 (API only, Sanctum, ruoli)
  Dockerfile             # immagine: php:8.5-fpm + nginx + supervisord
  docker/                # nginx.conf, supervisord.conf, entrypoint.sh
  app/Http/Controllers/Api/   # Auth, Exam, Vote
  app/Http/Middleware/EnsureRole.php
  app/Services/NagerHolidayService.php
  app/Enums/Role.php
  database/migrations/        # users(role), exams, exam_user
  tests/Feature/              # feature test
frontend/                # React + TS + Vite
  Dockerfile             # immagine: node:26
  docker/                # entrypoint.sh
docker-compose.yml
Makefile
```
