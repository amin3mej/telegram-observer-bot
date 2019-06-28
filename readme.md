# Observer Bot for Telegram

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)

# Installation
1. Clone the project
2. Rename `.env.example` to `.env`
3. Configure database connection settings in `.env` file
4. Generate a random string for `APP_KEY` var. (psst, you can use `openssl rand -hex 32`)
5. Fill in the `BOT_` variables with proper values
6. Use Artisan's serve command:
<pre><code>user@host:~$ <b>php artisan serve</b></code></pre>
or configure a webserver like "nginx" to reverse proxy via FPM or use CGI on `public` directory.

# Requirements

  * PHP (>= 7.0.0)
  * MySQL/MariaDB (>=5.6 / >=10.0)
