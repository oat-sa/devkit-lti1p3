# Installation and configuration documentation

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)

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

## Configuration

### Platforms, tools and registrations

Since this demo application relies on [LTI 1.3 symfony bundle](https://github.com/oat-sa/bundle-lti1p3), you can find [here](https://github.com/oat-sa/bundle-lti1p3/blob/master/doc/quickstart/configuration.md) instructions to configure it.

### Customization

You can find in the [config/demo](config/demo) folder configuration files to customize the LTI 1.3 demo application:
- [claims.yaml](config/demo/claims.yaml): configurable editor claims list
- [deep_linking.yaml](config/demo/deep_linking.yaml): configurable deep linking resources list
- [users.yaml](config/demo/users.yaml): configurable users list

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

