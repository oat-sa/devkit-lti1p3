# CLI documentation

## Table of Contents

- [Commands](#commands)
    - [LTI 1.3 messages launch generation command](#lti-13-messages-launch-generation-command)
      - [LtiResourceLinkRequest message](#ltiresourcelinkrequest-message)
      - [LtiDeepLinkingRequest message](#ltideeplinkingrequest-message)
      - [LtiStartProctoring message](#ltistartproctoring-message)
      - [LtiSubmissionReviewRequest message](#ltisubmissionreviewrequest-message)
  
## Commands

You can find below the available commands offered by the development kit.

### LTI 1.3 messages launch generation command

Can be used if you need to programmatically generate a typed LTI 1.3 message launch via command line.

Command details:
- command: `php bin/console devkit:create:message:launch`
- command help: `php bin/console devkit:create:message:launch --help`
- command options: 

| Name           | Short name  | Required | Description                                                  |
|----------------|-------------|----------|--------------------------------------------------------------|
| --type         | -t          | yes      | type of LTI 1.3 message launch to generate                   |
| --parameters   | -p          | yes      | parameters (JSON encoded) for the message launch generation  |
| --verbose      | -v          | no       | to output message launch details (disabled by default)       |

Common command execution example:

```shell
php bin/console devkit:create:message:launch -v -t LtiResourceLinkRequest -p '{
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

**Notes**: 

- setting up the option `-v` will not only return back the generated launch, but also the message launch details
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

Common command execution output example:

```shell
LTI 1.3 message launch link
-----------------------

 http://devkit-lti1p3.localhost/lti1p3/oidc/initiation?iss=http%3A%2F%2Fdevkit-lti1p3.localhost%2Fplatform&login_hint=%7B%22type%22%3A%22custom%22%2C%22user_id%22%3A%22userIdentifier%22%2C%22user_name%22%3Anull%2C%22user_email%22%3Anull%2C%22user_locale%22%3Anull%7D&target_link_uri=http%3A%2F%2Fdevkit-lti1p3.localhost%2Ftool%2Flaunch&lti_message_hint=eyJ0e...&lti_deployment_id=deploymentId1&client_id=client_id

LTI 1.3 message launch details
--------------------------

Url
---
http://devkit-lti1p3.localhost/lti1p3/oidc/initiation

Parameters
----------
iss
http://devkit-lti1p3.localhost/platform
login_hint
{"type":"custom","user_id":"userIdentifier","user_name":null,"user_email":null,"user_locale":null}
target_link_uri
http://devkit-lti1p3.localhost/tool/launch
lti_message_hint
eyJ0e...
lti_deployment_id
deploymentId1
client_id
client_id
```

**Notes**:
- `LTI 1.3 message launch link`: message launch link to use to perform later on the launch
- `LTI 1.3 message launch details`: message launch details, returned if `-v` is provided

#### LtiResourceLinkRequest message

Launch parameters (`--parameters`, JSON encoded) details:

| Name                                 | Required |Description                                                                                          |
|--------------------------------------|----------|-----------------------------------------------------------------------------------------------------|
| registration                         | yes      | registration identifier to use for the launch                                                       |
| user                                 | no       | user details to use for the launch                                                                  |
| target_link_uri                      | no       | target_link_uri to use for the launch, if not provided, will use default tool launch url            |
| deployment_id                        | no       | deployment_id to use for the launch, if not provided, will use default registration deployment id   |
| claims                               | no       | claims to use for the launch                                                                        |

Command execution example:

```shell
php bin/console devkit:create:message:launch -v -t LtiResourceLinkRequest -p '{
  "registration": "devkit",
  "user": {
    "id": "userIdentifier",
    "name": "User Name",
    "email": "user@email.com",
    "locale": "en"
  },
  "target_link_uri": "http://devkit-lti1p3.localhost/tool/launch",
  "deployment_id": "deploymentId1",
  "claims": {
    "a": "b",
    "https://purl.imsglobal.org/spec/lti/claim/roles": [
      "http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"
    ]
  }
}'
```

#### LtiDeepLinkingRequest message

Launch parameters (`--parameters`, JSON encoded) details:

| Name                                 | Required |Description                                                                                                          |
|--------------------------------------|----------|---------------------------------------------------------------------------------------------------------------------|
| registration                         | yes      | registration identifier to use for the launch                                                                       |
| deep_linking_settings                | no       | [deep linking settings](https://www.imsglobal.org/node/162911#deep-linking-settings) to use for the launch          |
| user                                 | no       | user details to use for the launch                                                                                  |
| target_link_uri                      | no       | target_link_uri to use for the launch, if not provided, will use default tool launch url                            |
| deployment_id                        | no       | deployment_id to use for the launch, if not provided, will use default registration deployment id                   |
| claims                               | no       | claims to use for the launch                                                                                        |

Command execution example:

```shell
php bin/console devkit:create:message:launch -v -t LtiDeepLinkingRequest -p '{
  "registration": "devkit",
  "deep_linking_settings": {
    "accept_types": ["link", "file", "html", "ltiResourceLink", "image"],
    "accept_media_types": "image/*,text/html",
    "accept_presentation_document_targets": ["iframe", "window", "embed"],
    "accept_multiple": true,
    "auto_create": false,
    "title": "This is the default title",
    "text": "This is the default text"
  },
  "user": {
    "id": "userIdentifier",
    "name": "User Name",
    "email": "user@email.com",
    "locale": "en"
  },
  "target_link_uri": "http://devkit-lti1p3.localhost/tool/launch",
  "deployment_id": "deploymentId1",
  "claims": {
    "a": "b",
    "https://purl.imsglobal.org/spec/lti/claim/roles": [
      "http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"
    ]
  }
}'
```

#### LtiStartProctoring message

Launch parameters (`--parameters`, JSON encoded) details:

| Name                                 | Required |Description                                                                                                          |
|--------------------------------------|----------|---------------------------------------------------------------------------------------------------------------------|
| registration                         | yes      | registration identifier to use for the launch                                                                       |
| proctoring_start_assessment_url      | yes      | platform start assessment url                                                                                       |
| proctoring_attempt_number            | no       | attempt number, default 1                                                                                           |
| user                                 | no       | user details to use for the launch                                                                                  |
| target_link_uri                      | no       | target_link_uri to use for the launch, if not provided, will use default tool launch url                            |
| deployment_id                        | no       | deployment_id to use for the launch, if not provided, will use default registration deployment id                   |
| claims                               | no       | claims to use for the launch                                                                                        |

Command execution example:

```shell
php bin/console devkit:create:message:launch -v -t LtiStartProctoring -p '{
  "registration": "devkit",
  "proctoring_start_assessment_url": "http://devkit-lti1p3.localhost/platform/message/return/proctoring",
  "proctoring_attempt_number": 1,
  "user": {
    "id": "userIdentifier",
    "name": "User Name",
    "email": "user@email.com",
    "locale": "en"
  },
  "target_link_uri": "http://devkit-lti1p3.localhost/tool/launch",
  "deployment_id": "deploymentId1",
  "claims": {
    "a": "b",
    "https://purl.imsglobal.org/spec/lti/claim/roles": [
      "http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"
    ]
  }
}'
```

#### LtiSubmissionReviewRequest message

Launch parameters (`--parameters`, JSON encoded) details:

| Name                                 | Required |Description                                                                                                          |
|--------------------------------------|----------|---------------------------------------------------------------------------------------------------------------------|
| registration                         | yes      | registration identifier to use for the launch                                                                       |
| ags_line_item_url                    | yes      | AGS line item url                                                                                                   |
| ags_scopes                           | yes      | AGS scopes                                                                                                          |
| submission_owner                     | no       | user who made the submission that is to be reviewed, if not provided, will use launch user information              |
| user                                 | no       | user details to use for the launch                                                                                  |
| target_link_uri                      | no       | target_link_uri to use for the launch, if not provided, will use default tool launch url                            |
| deployment_id                        | no       | deployment_id to use for the launch, if not provided, will use default registration deployment id                   |
| claims                               | no       | claims to use for the launch                                                                                        |

Command execution example:

```shell
php bin/console devkit:create:message:launch -v -t LtiSubmissionReviewRequest -p '{
  "registration": "devkit",
  "ags_line_item_url": "http://devkit-lti1p3.localhost/platform/service/ags/default/lineitems/default",
  "ags_scopes": [
    "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
    "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly",
    "https://purl.imsglobal.org/spec/lti-ags/scope/score",
    "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly"
  ],
  "submission_owner": {
    "id": "submissionOwnerIdentifier",
    "name": "Submission Owner",
    "email": "submission@owner.com",
    "roles": [
      "http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"
    ]
  },
  "user": {
    "id": "userIdentifier",
    "name": "User Name",
    "email": "user@email.com",
    "locale": "en"
  },
  "target_link_uri": "http://devkit-lti1p3.localhost/tool/launch",
  "deployment_id": "deploymentId1",
  "claims": {
    "a": "b",
    "https://purl.imsglobal.org/spec/lti/claim/roles": [
      "http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"
    ]
  }
}'
```