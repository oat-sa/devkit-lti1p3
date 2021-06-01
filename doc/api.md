# HTTP API documentation

## Table of Contents

- [Security](#security)
- [HTTP endpoints](#http-endpoints)
    - [ltiResourceLinkRequest launch generation endpoint](#ltiresourcelinkrequest-launch-generation-endpoint)

## Security

Since this demo application can be registered with real LMS production instances, the API HTTP endpoints are **protected by an API key**.

This API key is configurable on the [.env](../.env) file, in the `APP_API_KEY` environment variable.

Every API HTTP endpoint request must provide this key as a token bearer via the request header `Authorization: Bearer <token>`.

## HTTP endpoints

You can find below the available API HTTP endpoints offered by the demo application.

### ltiResourceLinkRequest launch generation endpoint

Can be used if you need to programmatically generate a `ltiResourceLinkRequest` message launch via HTTP call.

Endpoint details:
- method: `POST`
- path: `/api/platform/messages/ltiResourceLinkRequest/launch`

Endpoint parameters (JSON encoded):

| Name                                 | Required |Description                                                                                          |
|--------------------------------------|----------|-----------------------------------------------------------------------------------------------------|
| registration                         | yes      | registration identifier to use for the launch                                                       |
| user                                 | no       | user details to use for the launch                                                                  |
| target_link_uri                      | no       | target_link_uri to use for the launch, if not provided, will use default tool launch url            |
| deployment_id                        | no       | deployment_id to use for the launch, if not provided, will use default registration deployment id   |
| claims                               | no       | claims to use for the launch                                                                        |

**Note**:
- for the `user` parameter, you can provide this structure:
```json
"user": {
  "id": "userIdentifier",     [optional, will generate uuidv4 if not provided]
  "name": "user name",        [optional]
  "email": "user@mail.com",   [optional]
  "locale": "en"              [optional]
}
```
- for the `claims` parameter, you can provide any claim form the [IMS specifications](http://www.imsglobal.org/spec/lti/v1p3/#required-message-claims), for example:
```json
"claims": {
  "a": "b",
  "https://purl.imsglobal.org/spec/lti/claim/roles": [
    "http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"
  ]
}
```


Endpoint request example:
```shell
curl --location --request POST 'http://localhost:8888/api/platform/messages/ltiResourceLinkRequest/launch?verbose=true' \
--header 'Authorization: Bearer xxxxx' \
--header 'Content-Type: application/json' \
--data-raw '{
  "registration": "demo",
  "user": {
    "id": "userIdentifier"
  },
  "claims": {
    "a": "b",
    "https://purl.imsglobal.org/spec/lti/claim/roles": [
      "http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"
    ]
  }
}'
```

**Note**: setting up the optional query parameter `verbose=true` will mot only return back the generated launch, but also the message launch details.

Endpoint response example:

```json
{
    "link": "http://localhost:8888/lti1p3/oidc/initiation?iss=http%3A%2F%2Flocalhost%3A8888%2Fplatform&login_hint=%7B%22type%22%3A%22anonymous%22%7D&target_link_uri=http%3A%2F%2Flocalhost%3A8888%2Ftool%2Flaunch&lti_message_hint=eyJ0eXAiO...&lti_deployment_id=deploymentId1&client_id=client_id",
    "details": {
        "url": "http://localhost:8888/lti1p3/oidc/initiation",
        "parameters": {
            "iss": "http://localhost:8888/platform",
            "login_hint": "{\"type\":\"anonymous\"}",
            "target_link_uri": "http://localhost:8888/tool/launch",
            "lti_message_hint": "eyJ0eXAiO...",
            "lti_deployment_id": "deploymentId1",
            "client_id": "client_id"
        }
    }
}
```

**Notes**:
- `link`: message launch link to use to perform later on the launch
- `details`: message launch details, returned if `verbose=true`