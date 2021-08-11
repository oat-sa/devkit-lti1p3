# HTTP API documentation

## Table of Contents

- [HTTP API security](#http-api-security)
- [HTTP API endpoints](#http-api-endpoints)

## HTTP API security

Since this development kit can be registered with real LMS production instances, the HTTP API endpoints are **protected by an API key**.

This API key is configurable on the [.env](../.env) file, in the `APP_API_KEY` environment variable.

Every HTTP API endpoint request must provide this key as a token bearer via the request header `Authorization: Bearer <token>`.

## HTTP API endpoints

The development kit HTTP endpoints are described in the [openapi documentation](openapi/devkit.yaml).

