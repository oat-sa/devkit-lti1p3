CHANGELOG
=========

1.6.0
-----

* Added possibility to provide custom user on launch (for resource link and deep linking)

1.5.0
-----

* Added possibility to configure resource link at launch (fallback to auto generated one when not specified)
* Added resource link claim in claims editor

1.4.0
-----

* Added PHP 8 support (and kept >=7.2)
* Added version indicator on homepage
* Updated all LTI dependencies (for PHP 8 support)
* Updated demo app code to match new LTI dependencies breaking changes

1.3.0
-----

* Added claims editor JSON validation (lti resource link, deep link)
* Added possibility to share generator forms via url (lti resource link, deep link, tool service client)

1.2.0
-----

* Added more details to tool generic service client response interface

1.1.0
-----

* Added launch url editors for LTI launch and deep linking
* Reworked interface for services callers

1.0.0
-----

* Added platform side message features (LTI resource link launch, deep linking launch)
* Added platform side service features (NRPS server, basic outcome server)
* Added tool side message features (LTI resource link launch, deep linking content selection)
* Added tool side service features (NRPS client, basic outcome client, generic service client)
* Added docker & k8s compliance
* Added documentation
