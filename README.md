# LanOps Event Management Platform

From the original Eventula Management Platform comes the next iteration of the Lan Management Platform. Built For LanOps. 

I originally built the eventula manager as a fully whitelabelled solution. The original build had a central hub to deploy these fully bespoke and white labelled Event Management Solutions as well as a searchable google map. Unfortunately through time I've not been able to keep up with its maintainence. However some wonderful people have decided to fork it and have a go at her themselves! 

### If you are wanting a fully white labelled event management system go look at the [Lan2Play Fork](https://github.com/Lan2Play/eventula-manager)

This code base has been specifically updated for LanOps. It can still be used for other events but would require you're own fork of the repo.

Built with Vodka and Hatred ontop of Laravel. Full Docker stack included.

## Stack

- Laravel 13 on PHP 8.5 (PHP-FPM), MariaDB 12
- Bootstrap 5.3 with light and dark mode, built with Vite 8 on Node 24
- Stripe for card payments (PaymentIntents, including 3D Secure)
- Steam and email/password login, optional Discord account linking
- Sentry for error reporting
- Docker images built by Drone and pushed to Docker Hub (`lanopsdev/lanops-manager`)

The Laravel app lives in `src/`. Docker files are in `resources/docker/` (`php`, `nginx`, and `ci` for the image the CI steps run in).

## Installation

### Prerequisites

- Docker v24+
- Docker compose v2.21+
- Make (optional but makes life a little easier)

### Steps

- ```cp src/.env.example src/.env```
- Fill that sucker in (see [ENV](#env) below)
- ```make build-init```
- ```docker compose up -d```

The site is then on http://localhost:8080.

`make build-init` builds the images, installs Composer and NPM packages, builds the front end, generates `APP_KEY`, runs the migrations and creates the `storage` link. Other handy targets:

| Command                | What it does                                       |
|------------------------|----------------------------------------------------|
| `make build`           | Composer install, NPM install and front-end build  |
| `make npm-build`       | Rebuild the front end only (`vite build`)          |
| `make artisan-db-migrate` | Run new migrations                              |
| `make artisan-db-seed` | Seed the database with sample data                 |
| `make artisan-db-fresh` | Drop everything and re-run all migrations         |

Any other artisan command can be run with `docker compose run --rm artisan <command>`.

### Making yourself an admin

Register an account on the site, then set `admin` to `1` on your row in the `users` table. After that you can grant or remove admin for other users from **Admin > Users**.

### Services

`docker compose up -d` starts:

| Service        | What it does                                                                 |
|----------------|------------------------------------------------------------------------------|
| `php`          | PHP-FPM running the app                                                      |
| `nginx`        | Web server on port 8080, serves `public/` and passes PHP to `php`            |
| `db`           | MariaDB 12                                                                   |
| `queue-worker` | Two `queue:work` workers (image conversions for uploads run on the queue)    |
| `cron`         | Runs `schedule:run` every minute (posts timetable updates to the Discord bot)|

`composer`, `npm` and `artisan` are one-off helpers used by the Makefile.

## Deployment

Drone (`.drone.yml`) runs lint (`phpcs`) and the tests on every push. Only pushes to `main` and git tags also build and push the images:

| Branch / tag | Images                                  |
|--------------|-----------------------------------------|
| `main`       | `app-main`, `nginx-main`                |
| tag `v1.2.3` | `app-v1.2.3`, `nginx-v1.2.3`            |

The front end is built inside the image, so the server only needs to pull the new images and restart. The containers run `php artisan optimize` on start, so config changes in `.env` need a restart to take effect.

After a deploy that adds migrations, run:

- ```php artisan migrate --force```

Other useful artisan commands:

- ```php artisan tickets:regenerate-qr``` regenerates ticket QR codes (add `--event=<id>` for one event only)

The CI image (`lanopsdev/lanops-manager:ci-php8.5`) is built by a separate Drone pipeline. Run it with **New Build** in Drone when its Dockerfile changes; a Drone cron job also rebuilds it so PHP security updates reach CI.

### Tests

Feature tests live in `src/tests` and run in Drone on every push, against a MariaDB service. They cover the main pages, the Stripe checkout (with the Stripe API faked), refunds, private events, polls, login, registration and password reset.

To run them locally you need a MariaDB database called `lanops_test` that the user `lanops` (password `secret`) can use. `phpunit.xml` forces those settings so the tests can never touch your real database. Then:

- ```cd src && vendor/bin/phpunit```

If you have run `php artisan optimize` locally, run `php artisan optimize:clear` first, or the cached config makes phpunit ignore `phpunit.xml`.

Lint with:

- ```cd src && vendor/bin/phpcs```

## Features

- Event Management
  - Signup/Info Pages
  - Draft, preview, published and private events
  - Private events are hidden from all listings and opened with a secret link (`/e/<key>`) that admins can regenerate
  - Tickets, with stock limits
  - Signups/Gifts/Staff
  - Refunds through Stripe from the admin purchase page
  - Timetables
  - Seating
- Ticket Management
  - Card payments through Stripe, with 3D Secure
  - Referral codes with a discount off the next ticket
  - Ticket QR codes, shown on the payment success page and on the account page
- Seating Plans
- Venue Management
- User Management
  - Admin rights, bans, and changing a user's email address
- Timetable Management
- Tournaments
- Event Sign in
  - Sign in via QR Code
- Event specific page for when at the event
  - Shows Timetables, Attendees, Tournaments, Announcements and Seating
  - "Show my ticket" banner during a live event
- Bigscreen pages for the current event's timetable and seating (`/bigscreen/timetable`, `/bigscreen/seating`)
- Gallery
- Voting and Poll Management, with optional end dates
- News Management
- Discord account linking and timetable announcements through the LanOps Discord bot
- Admin Interface, with sales and ticket charts
- Sitemap
- API Endpoints (read only, under `/api`)
  - `/events`, `/events/upcoming`, `/events/next`, `/events/{event}`
  - `/events/{event}/timetables`, `/events/{event}/timetables/default`, `/events/{event}/timetables/{timetable}`

## ENV

All settings go in `src/.env`. `src/.env.example` has a starting point for local dev.

### App

| Key                          | Required | Default                 | Notes                                                                    |
|------------------------------|----------|-------------------------|--------------------------------------------------------------------------|
| APP_KEY                      | True     |                         | `make build-init` generates it                                           |
| APP_DEBUG                    | False    | false                   | Never `true` in production                                               |
| APP_ENV                      | False    | production              | `local` for local dev                                                    |
| APP_NAME                     | True     | LanOps                  | Also used to name the basket cookie                                      |
| APP_TAGLINE                  | True     | Lans in South Yorkshire! |                                                                         |
| APP_URL                      | True     | localhost               | Full URL including `https://`. Used in emails, QR codes and Steam login  |
| APP_EMAIL                    | False    | no-reply@localhost      | From address on emails                                                   |
| REFER_A_FRIEND_DISCOUNT      | False    | 10                      | Referral discount, in pounds                                             |
| CAPTCHA_DISABLE              | False    | false                   | Turns off the captcha on registration                                    |

### Database

| Key                          | Required | Default                 | Notes                                                                    |
|------------------------------|----------|-------------------------|--------------------------------------------------------------------------|
| DB_DATABASE                  | True     |                         |                                                                          |
| DB_USERNAME                  | True     |                         |                                                                          |
| DB_PASSWORD                  | True     |                         |                                                                          |
| DB_HOST                      | True     |                         | `db` with the bundled MariaDB container                                  |
| DB_CONNECTION                | False    | mysql                   |                                                                          |
| DB_PORT                      | False    | 3306                    |                                                                          |
| MARIADB_*                    | False    |                         | Only for the bundled `db` container, see `.env.example`                  |

### Payments and login

| Key                          | Required | Default                 | Notes                                                                    |
|------------------------------|----------|-------------------------|--------------------------------------------------------------------------|
| STRIPE_PUBLIC_KEY            | False    |                         | Card payments only show when both Stripe keys are set                    |
| STRIPE_SECRET_KEY            | False    |                         |                                                                          |
| STEAM_API_KEY                | False    |                         | Used for Steam Login                                                     |
| STEAM_ENABLE_HTTPS           | False    | true                    | Set `false` for local dev on plain http                                  |

### Email

Used for password resets and email verification.

| Key                          | Required | Default                 | Notes                                                                    |
|------------------------------|----------|-------------------------|--------------------------------------------------------------------------|
| MAIL_MAILER                  | False    | log                     | Set to `smtp` to actually send email; `log` only writes it to the log    |
| MAIL_HOST                    | False    | 127.0.0.1               |                                                                          |
| MAIL_PORT                    | False    | 2525                    |                                                                          |
| MAIL_USERNAME                | False    |                         |                                                                          |
| MAIL_PASSWORD                | False    |                         |                                                                          |

### Discord

| Key                          | Required | Default                 | Notes                                                                    |
|------------------------------|----------|-------------------------|--------------------------------------------------------------------------|
| DISCORD_LINK                 | False    |                         | Invite link, shows the "Join the Server" box on the home page            |
| DISCORD_BOT_URL              | False    |                         | LanOps Discord bot, for account linking and timetable announcements      |
| DISCORD_BOT_USER             | False    |                         |                                                                          |
| DISCORD_BOT_PASS             | False    |                         |                                                                          |
| DISCORD_BOT_SECRET           | False    |                         |                                                                          |
| DISCORD_CLIENT_ID            | False    |                         | Discord OAuth app, for linking accounts                                  |
| DISCORD_CLIENT_SECRET        | False    |                         |                                                                          |
| DISCORD_REDIRECT_URL         | False    |                         |                                                                          |
| DISCORD_SCOPE                | False    | identify                |                                                                          |

### Social, SEO and tracking

| Key                          | Required | Default                 | Notes                                                                    |
|------------------------------|----------|-------------------------|--------------------------------------------------------------------------|
| FACEBOOK_LINK                | False    |                         | Footer links, hidden when empty                                          |
| STEAM_LINK                   | False    |                         |                                                                          |
| YOUTUBE_LINK                 | False    |                         |                                                                          |
| BSKY_LINK                    | False    |                         |                                                                          |
| SEO_DESCRIPTION              | False    |                         |                                                                          |
| SEO_KEYWORDS                 | False    | (LanOps keywords)       |                                                                          |
| GOOGLE_TAG_ID                | False    |                         | Google Analytics, off when empty                                         |
| SENTRY_LARAVEL_DSN           | False    |                         | Sentry error reporting, off when empty                                   |
| SENTRY_TRACES_SAMPLE_RATE    | False    |                         | Share of requests traced, `0` to `1`                                     |
