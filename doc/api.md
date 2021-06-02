# HTTP API documentation

## Table of Contents

- [Security](#security)
- [HTTP endpoints](#http-endpoints)
    - [ltiResourceLinkRequest launch generation endpoint](#ltiresourcelinkrequest-launch-generation-endpoint)

## Security

Since this development kit application can be registered with real LMS production instances, the API HTTP endpoints are **protected by an API key**.

This API key is configurable on the [.env](../.env) file, in the `APP_API_KEY` environment variable.

Every API HTTP endpoint request must provide this key as a token bearer via the request header `Authorization: Bearer <token>`.

## HTTP endpoints

You can find below the available API HTTP endpoints offered by the development kit application.

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
  "registration": "devkit",
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
  "link": "http://devkit-lti1p3.localhost/lti1p3/oidc/initiation?iss=http%3A%2F%2Fdevkit-lti1p3.localhost%2Fplatform&login_hint=%7B%22type%22%3A%22custom%22%2C%22user_id%22%3A%22userIdentifier%22%2C%22user_name%22%3Anull%2C%22user_email%22%3Anull%2C%22user_locale%22%3Anull%7D&target_link_uri=http%3A%2F%2Fdevkit-lti1p3.localhost%2Ftool%2Flaunch&lti_message_hint=eyJ0e...&lti_deployment_id=deploymentId1&client_id=client_id",
  "details": {
    "url": "http://devkit-lti1p3.localhost/lti1p3/oidc/initiation",
    "parameters": {
      "iss": "http://devkit-lti1p3.localhost/platform",
      "login_hint": "{\"type\":\"custom\",\"user_id\":\"userIdentifier\",\"user_name\":null,\"user_email\":null,\"user_locale\":null}",
      "target_link_uri": "http://devkit-lti1p3.localhost/tool/launch",
      "lti_message_hint": "eyJ0e...",
      "lti_deployment_id": "deploymentId1",
      "client_id": "client_id"
    }
  }
}
```

**Notes**:
- `link`: message launch link to use to perform later on the launch
- `details`: message launch details, returned if `verbose=true`