# BasinEnergy.info

BasinEnergy.info is a Laravel-based web platform developed and maintained by the Bingham Research Center at Utah State University. This document is the onboarding and development workflow for contributors working on the project.

**Production website:** https://www.basinenergy.info/  
**Official repository:** https://github.com/Bingham-Research-Center/basin-energy

> **Development rule:** Do not develop directly on `master`. All work should be completed in a feature/fix branch in your personal fork and submitted to the official repository through a pull request (PR).

---

## 1. Development workflow at a glance

```text
Official repository (upstream/master)
          |
          v
      Fork to your GitHub account
          |
          v
      Clone your fork locally
          |
          v
      Create feature/fix branch
          |
          v
      Develop + test locally
          |
          v
      Rebase onto upstream/master
          |
          v
      Push branch to your fork
          |
          v
      Pull Request -> upstream/master
          |
          v
      CI tests + review
          |
          v
      Rebase-and-merge to master
          |
          v
      Production deployment
```

Approved changes on `master` are normally deployed to production **every Friday at 12:00 PM Mountain Time**, unless otherwise announced.

---

## 2. Prerequisites

Install the following before starting:

- Git
- Docker Engine with Docker Compose, or Docker Desktop
- Node.js 20.x and npm
- A GitHub account with access to the project
- A code editor such as VS Code

The sample Docker configuration in this repository was developed and tested on Ubuntu. It provides:

- PHP 8.2 + Apache for Laravel
- MySQL 8.0
- Laravel Reverb
- phpMyAdmin

---

## 3. Fork the repository

Open the official repository:

https://github.com/Bingham-Research-Center/basin-energy

Select **Fork** and create a fork under your personal GitHub account.

Your fork will look similar to:

```text
https://github.com/YOUR-GITHUB-USERNAME/basin-energy
```

The Bingham Research Center repository is referred to as **upstream** throughout this guide, while your personal fork is referred to as **origin**.

---

## 4. Clone your fork

Using SSH:

```bash
git clone git@github.com:YOUR-GITHUB-USERNAME/basin-energy.git
cd basin-energy
```

Or using HTTPS:

```bash
git clone https://github.com/YOUR-GITHUB-USERNAME/basin-energy.git
cd basin-energy
```

Add the official repository as `upstream`:

```bash
git remote add upstream git@github.com:Bingham-Research-Center/basin-energy.git
```

If you use HTTPS instead of SSH:

```bash
git remote add upstream https://github.com/Bingham-Research-Center/basin-energy.git
```

Verify the remotes:

```bash
git remote -v
```

Expected structure:

```text
origin    -> your personal fork
upstream  -> Bingham-Research-Center/basin-energy
```

---

## 5. Prepare the local Docker environment

Local-development Docker templates are stored in:

```text
scripts/local-dev/
├── Dockerfile
└── docker-compose.yml
```

Copy them to the repository root:

```bash
cp scripts/local-dev/Dockerfile ./Dockerfile
cp scripts/local-dev/docker-compose.yml ./docker-compose.yml
```

These root-level Docker files are intended for the developer's local environment. Do not commit local or machine-specific Docker changes to a feature branch unless the change is intentionally updating the shared templates in `scripts/local-dev/`.

The supplied Docker Compose configuration exposes the following local services:

| Service | Local address |
| --- | --- |
| BasinEnergy website | http://localhost:8081 |
| Laravel Reverb | http://localhost:8082 |
| phpMyAdmin | http://localhost:8083 |
| MySQL from the host | `127.0.0.1:3307` |

Inside Docker, Laravel communicates with MySQL using host `mysql` and port `3306`.

---

## 6. Create the Laravel environment file

Create your local `.env` file:

```bash
cp .env.example .env
```

For the supplied Docker Compose configuration, ensure the local database settings include:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8081

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=basin_energy
DB_USERNAME=basin_user
DB_PASSWORD=basin_password
```

Do not commit `.env`, API keys, passwords, database dumps, or other secrets to GitHub.

---

## 7. Build and start the local containers

From the repository root:

```bash
docker compose up -d --build
```

Check that the containers are running:

```bash
docker compose ps
```

The expected containers include:

```text
brc_site
brc_reverb
brc_mysql
brc_phpmyadmin
```

Install PHP dependencies inside the Laravel container:

```bash
docker exec -it brc_site composer install
```

Generate the local Laravel application key:

```bash
docker exec -it brc_site php artisan key:generate
```

Clear any stale Laravel configuration/cache:

```bash
docker exec -it brc_site php artisan optimize:clear
```

---

## 8. Install and build frontend dependencies

The provided PHP Docker image does not include Node.js. Install Node.js 20.x on your local machine and run the frontend build from the repository root:

```bash
npm ci
npm run dev
```

For a production-style frontend build:

```bash
npm run production
```

Generated frontend files should not be treated as normal source-code edits unless a project maintainer specifically requests them.

---

## 9. Obtain and import the development database

The application requires project data that is not stored in GitHub.

A current development `.sql` database dump can be provided **on request by the project administrator, Arjun**.

> Database dumps may contain project data and must not be committed to Git, uploaded to GitHub, or distributed outside authorized project members.

After receiving the SQL dump, import it into the local MySQL container. For example:

```bash
docker exec -i brc_mysql \
  mysql -ubasin_user -pbasin_password basin_energy \
  < /path/to/basin-energy-development.sql
```

After importing, apply any migrations added after the database dump was created:

```bash
docker exec -it brc_site php artisan migrate
```

phpMyAdmin is also available at:

```text
http://localhost:8083
```

For large database dumps, the command-line import is recommended.

---

## 10. reCAPTCHA for local development

BasinEnergy uses Google reCAPTCHA for selected public forms, including authentication when enabled.

For routine development, reCAPTCHA can be disabled locally in `.env`:

```dotenv
LOGIN_CAPTCHA_STATUS=false
REGISTRATION_CAPTCHA_STATUS=false
CONTACT_CAPTCHA_STATUS=false
```

Then clear Laravel's cached configuration:

```bash
docker exec -it brc_site php artisan config:clear
```

If your work specifically involves CAPTCHA behavior, create/use appropriate **development reCAPTCHA v2 / Invisible reCAPTCHA keys for localhost** and configure:

```dotenv
INVISIBLE_RECAPTCHA_SITEKEY=your-development-site-key
INVISIBLE_RECAPTCHA_SECRETKEY=your-development-secret-key

LOGIN_CAPTCHA_STATUS=true
REGISTRATION_CAPTCHA_STATUS=true
CONTACT_CAPTCHA_STATUS=true
```

Never commit reCAPTCHA secret keys to the repository.

---

## 11. Verify the local application

Open:

```text
http://localhost:8081
```

Useful checks:

```bash
docker exec -it brc_site php artisan about
```

```bash
docker exec -it brc_site php artisan route:list
```

Run the PHPUnit test suite before submitting a PR:

```bash
docker exec -it brc_site ./vendor/bin/phpunit
```

All tests should pass before the branch is submitted for review.

---

## 12. Start a new feature or bug fix

Always begin from an up-to-date `master`.

Switch to local `master`:

```bash
git switch master
```

Fetch the official repository:

```bash
git fetch upstream
```

Fast-forward local `master` to the official `master`:

```bash
git merge --ff-only upstream/master
```

Optionally synchronize the `master` branch in your fork:

```bash
git push origin master
```

Create a new branch:

```bash
git switch -c feature/short-description
```

Examples:

```text
feature/new-emissions-view
fix/login-validation
chore/update-dependencies
```

Do not make feature-development commits directly on `master`.

---

## 13. Commit your work

Review your changes before committing:

```bash
git status
git diff
```

Stage only the intended files:

```bash
git add path/to/file1 path/to/file2
```

Commit with a short, meaningful message:

```bash
git commit -m "Add methane emissions summary view"
```

Avoid committing:

- `.env`
- database `.sql` dumps
- passwords or API keys
- editor/IDE temporary files
- local logs
- machine-specific configuration
- unintended generated files

---

## 14. Rebase onto the latest upstream master

Before opening or updating a PR, bring your branch up to date with the official repository:

```bash
git fetch upstream
git rebase upstream/master
```

If Git reports a conflict:

1. Resolve the conflicting files.
2. Stage the resolved files:

```bash
git add <resolved-file>
```

3. Continue the rebase:

```bash
git rebase --continue
```

Repeat until the rebase finishes.

Do not create a merge commit merely to update your feature branch from `master`. The project uses a linear/rebase-based history.

---

## 15. Push your feature branch to your fork

For the first push:

```bash
git push -u origin feature/short-description
```

If the branch was already pushed and you rebased it afterward, update your fork safely with:

```bash
git push --force-with-lease origin feature/short-description
```

Use `--force-with-lease`, not plain `--force`.

---

## 16. Open a pull request

On GitHub, create a PR with:

```text
Base repository: Bingham-Research-Center/basin-energy
Base branch:     master

Head repository: YOUR-GITHUB-USERNAME/basin-energy
Compare branch:  feature/short-description
```

The PR should contain a concise description of:

- what was changed;
- why the change is needed;
- how it was tested;
- any database/configuration changes;
- screenshots for meaningful UI changes, when useful.

GitHub Actions will run the Laravel CI workflow for PRs targeting `master`. Address test failures and reviewer comments before merge.

The repository uses a linear Git history. PRs should be **rebased and merged** rather than merged with an extra merge commit.

---

## 17. After a PR is merged

Update your local `master`:

```bash
git switch master
git fetch upstream
git merge --ff-only upstream/master
```

Synchronize your fork:

```bash
git push origin master
```

Delete the merged local feature branch:

```bash
git branch -d feature/short-description
```

Delete the merged branch from your fork:

```bash
git push origin --delete feature/short-description
```

Clean stale remote references:

```bash
git fetch --all --prune
```

---

## 18. Production deployment schedule

Developers do not deploy feature branches directly to production.

The normal release flow is:

```text
Developer PR
    -> CI + review
    -> upstream/master
    -> scheduled production update
```

Approved changes in `master` are normally deployed to the production BasinEnergy.info server **every Friday at 12:00 PM Mountain Time**, unless an urgent deployment or alternate schedule is announced by the project administrator.

Before the scheduled deployment, developers should ensure that any required migration steps, environment-variable changes, or other deployment notes are clearly documented in the PR.

---

## 19. Useful Docker commands

Start the development environment:

```bash
docker compose up -d
```

Stop it:

```bash
docker compose down
```

View containers:

```bash
docker compose ps
```

View Laravel/Apache logs:

```bash
docker logs -f brc_site
```

Open a shell in the Laravel container:

```bash
docker exec -it brc_site bash
```

Open Laravel Tinker:

```bash
docker exec -it brc_site php artisan tinker
```

Restart the website container:

```bash
docker restart brc_site
```

Rebuild after a Dockerfile change:

```bash
docker compose up -d --build
```

---

## 20. Getting help

For the following, contact the project administrator, **Arjun**:

- development database (`.sql`) access;
- repository/access questions;
- development environment questions;
- deployment coordination;
- project-specific credentials or service configuration.

When reporting a development problem, include relevant error output, the branch name, and the command that produced the error. Do not include passwords, secret keys, or other credentials in GitHub issues or chat messages.