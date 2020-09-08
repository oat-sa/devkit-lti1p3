# LTI 1.3 Demo Application

> [Symfony](https://symfony.com/) demo application for LTI 1.3, to act as a [LTI Platform and Tool](http://www.imsglobal.org/spec/lti/v1p3/#platforms-and-tools-0).

This demo application based on:
- the [lti1p3 symfony bundle](https://github.com/oat-sa/bundle-lti1p3)
- the [lti1p3-core library](https://github.com/oat-sa/lib-lti1p3-core)

## Table of Contents

- [Specifications](#specifications)
- [Installation](#installation)
- [Usage](#usage)

## Specifications
 
 This demo application respect the following LTI 1.3 [IMS](http://www.imsglobal.org) specifications:
- [IMS LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3)
- [IMS Security](https://www.imsglobal.org/spec/security/v1p0)

## Installation

You need to ensure first you have the following installed:
- [docker](https://docs.docker.com/get-docker/)
- [docker-compose](https://docs.docker.com/compose/install/)

After cloning this repository, you can build the [provided docker stack](docker-compose.yml):
```console
$ docker-compose up -d
```

Then, install required dependencies with [composer](https://hub.docker.com/_/composer):
```console
$ docker run --rm --interactive --tty \
  --volume $PWD:/app \
  composer install
```

For Windows users:
- you may have to do `--volume %cd%:/app` instead
- with powershell, you may have to do `--volume ${PWD}:/app` instead

## Usage

### Application

After installation, the LTI 1.3 demo application is available on [http://localhost:8888](http://localhost:8888)

### Services

After installation, the following services are available:

| Name                                 | Description                      |
|--------------------------------------|----------------------------------|
| demo_lti1p3_nginx                    | application nginx web server     |
| demo_lti1p3_phpfpm                   | application php-fpm              |
| demo_lti1p3_redis                    | application cache                |
| demo_lti1p3_redis_commander          | application cache administration |

You can access:

| Name                                 | URL                                            |
|--------------------------------------|------------------------------------------------|
| demo_lti1p3_nginx                    | [http://localhost:8888](http://localhost:8888) |
| demo_lti1p3_redis_commander          | [http://localhost:8081](http://localhost:8081) |

### Mode

By default, this application runs in `dev` mode:
- to avoid updating symfony cache on each configuration change
- to be able to get debugging feedback on errors

If you need production performances, please update to `APP_ENV=prod` in the [.env](.env) file.

**Note**: on each configuration update in prod mode, you'll then need to clear the application cache:

```console
$ docker exec -it demo_lti1p3_phpfpm php bin/console ca:cl
```
