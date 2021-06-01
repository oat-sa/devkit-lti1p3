CHANGELOG
=========

1.10.0
-----

* Added LTI messages generation API (HTTP and CLI)
* Added LTI messages generation API documentation
* Fixed jQuery assets CDN
* Fixed AGS claim scope parameter for claim editor

1.9.1
-----

* Added heath check bundle
* Fixed main css file url
* Fixed absolute urls generation

1.9.0
-----

* Added ACS claim to editor
* Added platform CRUD for proctoring test sessions
* Added proctoring messages flow handling  
* Added autocomplete fields for scope and media type on service client interface
* Updated NRPS and basic outcome services endpoints with bundle request handler abstraction
* Updated LTI core library and bundle to version 6.x
* Updated sidebar menus and templates headers
* Updated default registration name from local to demo  
* Updated default users roles to match IMS roles vocabulary
* Updated claim editor dropdown to be multi columns
* Merged all message launch endpoints into a central one

1.8.0
-----

* Added platform basic outcome list
* Updated platform NRPS memberships list

1.7.0
-----

* Added default NRPS membership (based on users configuration file)
* Added platform CRUD for custom NRPS memberships (cache storage)
* Updated default NRPS service url claim editor to match default NRPS membership
* Updated context identifier claim editor to default

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
