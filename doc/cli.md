# CLI documentation

## Table of Contents

- [Commands](#commands)
    - [Message launch generation command](#message-launch-generation-command)


## Commands

You can find below the available commands offered by the demo application.

### Message launch generation command

Can be used if you need to programmatically generate a `ltiResourceLinkRequest` message launch via a command line (only message type supported for now).

Command details:
- command: `php bin/console create:message:launch`
- command help: `php bin/console create:message:launch --help`
- command options: 

| Name           | Short name  | Required | Description                                                  |
|----------------|-------------|----------|--------------------------------------------------------------|
| --type         | -t          | yes      | type of LTI message launch to generate                       |
| --parameters   | -p          | yes      | parameters (JSON encoded) for the message launch generation  |
| --verbose      | -v          | no       | to output message launch details (default = no)              |


Launch parameters (`--parameters`, JSON encoded) details:

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


Command execution example:
```shell
php bin/console create:message:launch -v -t LtiResourceLinkRequest -p '{
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

**Note**: setting up the option `-v` will mot only return back the generated launch, but also the message launch details.

Command execution output example:

```shell
LTI message launch link
-----------------------

 http://localhost:8888/lti1p3/oidc/initiation?iss=http%3A%2F%2Flocalhost%3A8888%2Fplatform&login_hint=%7B%22type%22%3A%22custom%22%2C%22user_id%22%3A%22jonathan%22%2C%22user_name%22%3Anull%2C%22user_email%22%3Anull%2C%22user_locale%22%3Anull%7D&target_link_uri=http%3A%2F%2Flocalhost%3A8888%2Ftool%2Flaunch&lti_message_hint=eyJ0eXAiOiJKV1Q...&lti_deployment_id=deploymentId1&client_id=client_id

LTI message launch details
--------------------------

Url
---
http://localhost:8888/lti1p3/oidc/initiation

Parameters
----------
iss
http://localhost:8888/platform
login_hint
{"type":"custom","user_id":"jonathan","user_name":null,"user_email":null,"user_locale":null}
target_link_uri
http://localhost:8888/tool/launch
lti_message_hint
eyJ0eXAiOiJKV1Q...
lti_deployment_id
deploymentId1
client_id
client_id
```

**Notes**:
- `LTI message launch link`: message launch link to use to perform later on the launch
- `LTI message launch details`: message launch details, returned if `-v` is provided