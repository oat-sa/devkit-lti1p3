# LTI 1.3 Demo Application

> [Symfony](https://symfony.com/) demo application for LTI 1.3, to act as a [LTI platform and / or tool](http://www.imsglobal.org/spec/lti/v1p3/#platforms-and-tools-0).

This demo application based on the following packages:
- [LTI 1.3 symfony bundle](https://github.com/oat-sa/bundle-lti1p3)
- [LTI 1.3 deep linking library](https://github.com/oat-sa/lib-lti1p3-deep-linking)
- [LTI 1.3 NRPS library](https://github.com/oat-sa/lib-lti1p3-nrps)
- [LTI 1.3 core library](https://github.com/oat-sa/lib-lti1p3-core)


## Table of Contents

- [Live demo](#live-demo)
- [Specifications](#specifications)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)

## Live demo
 
To try it live: [https://lti.showcase.gcp.taocloud.org](https://lti.showcase.gcp.taocloud.org)

## Specifications
 
This demo application respect the following LTI 1.3 [IMS](http://www.imsglobal.org) specifications:
- [IMS Security](https://www.imsglobal.org/spec/security/v1p0)
- [IMS LTI 1.3 Names and Role Provisioning Services](https://www.imsglobal.org/spec/lti-nrps/v2p0)
- [IMS LTI 1.3 Deep Linking](https://www.imsglobal.org/spec/lti-dl/v2p0/)
- [IMS LTI 1.3 Core](http://www.imsglobal.org/spec/lti/v1p3)

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

Since this demo application relies on [LTI 1.3 symfony bundle](https://github.com/oat-sa/bundle-lti1p3), you can find [here](https://github.com/oat-sa/bundle-lti1p3/blob/master/doc/quickstart/configuration.md) instructions to configure it.

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

