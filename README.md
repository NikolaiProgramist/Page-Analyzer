# Page Analyzer

[![Actions Status](https://github.com/NikolaiProgramist/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/NikolaiProgramist/php-project-9/actions) [![tests](https://github.com/NikolaiProgramist/php-project-9/actions/workflows/tests-check.yml/badge.svg)](https://github.com/NikolaiProgramist/php-project-9/actions/workflows/tests-check.yml) [![Maintainability](https://qlty.sh/badges/279dd9ca-14ec-40cb-bb83-012fdfb92d0d/maintainability.svg)](https://qlty.sh/gh/NikolaiProgramist/projects/php-project-9)

## About

This web service helps to get more information from internet pages.
The project is written in `slim 4` micro framework.
You can get such information as: response `status`, `h1`, `title` and `description`.
With this information, you will be able to improve the **SEO** quality of your pages.

**See the web service:** [Page Analyzer](https://page-analyzer-0wj3.onrender.com).

## Prerequisites

+ Linux, MacOS, WSL
+ PostgreSQL
+ PHP >=8.4
+ Composer
+ Make
+ Git

## Libraries

+ carbon
+ valitron
+ guzzle
+ didom
+ helpers

## How to use

### Install project

Downloading the project and installing dependencies:

```bash
git clone https://github.com/NikolaiProgramist/php-project-9.git
cd php-project-9
make install
```

### Create file with environment variables

Create an `.env` file and specify in it your data
to connect to the **PostgreSQL** database as specified in `.env.example`:

```dotenv
DATABASE_URL=postgresql://username:password@host:port/dbname
```

### Start server

Finally, start your **PHP Server**:

```bash
make start
```

You can see the result in your **browser**
by typing in the link: `localhost:8000`.

## Stargazers over time

[![Stargazers over time](https://starchart.cc/NikolaiProgramist/php-project-9.svg?variant=adaptive)](https://starchart.cc/NikolaiProgramist/php-project-9)
