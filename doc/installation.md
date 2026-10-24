# Installation and configuration

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)

## Installation

You need to ensure first you have the following installed:
- [docker](https://docs.docker.com/get-docker/)
- [docker-compose](https://docs.docker.com/compose/install/)

After cloning this repository, you can build the [provided docker stack](../docker-compose.yml):
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

After installation, the development kit is available on [http://devkit-lti1p3.localhost](http://devkit-lti1p3.localhost)

### Services

After installation, the following docker services are available:

| Name                                   | Description                      |
|----------------------------------------|----------------------------------|
| devkit_lti1p3_traefik                  | application proxy                |
| devkit_lti1p3_app                      | application server (FrankenPHP)  |
| devkit_lti1p3_redis                    | application cache                |
| devkit_lti1p3_redis_commander          | application cache administration |

You can access:

| Name                                   | URL                                                              |
|----------------------------------------|------------------------------------------------------------------|
| devkit_lti1p3_app                      | [http://localhost:8080](http://localhost:8080)                   |
| devkit_lti1p3_app (via Traefik)        | [http://devkit-lti1p3.localhost](http://devkit-lti1p3.localhost) |
| devkit_lti1p3_traefik                  | [http://localhost:8090](http://localhost:8090)                   |
| devkit_lti1p3_redis_commander          | [http://localhost:8081](http://localhost:8081)                   |

The application server is [FrankenPHP](frankenphp.md) running the Symfony
kernel in worker mode — a single container serves HTTP directly on port 8080,
there is no separate web server or PHP-FPM process. See
[FrankenPHP runtime](frankenphp.md) for worker configuration
(`WORKER_NUM`, `FRANKENPHP_LOOP_MAX`) and lifecycle notes.

## Configuration

### Platforms, tools and registrations

Since this development kit application relies on [LTI 1.3 symfony bundle](https://github.com/oat-sa/bundle-lti1p3), you can find [here](https://github.com/oat-sa/bundle-lti1p3/blob/master/doc/quickstart/configuration.md) instructions to configure it.

### Customization

You can find in the [config/devkit](../config/devkit) folder configuration files to customize the development kit:
- [claims.yaml](../config/devkit/claims.yaml): configurable editor claims list
- [deep_linking.yaml](../config/devkit/deep_linking.yaml): configurable deep linking resources list
- [users.yaml](../config/devkit/users.yaml): configurable users list
