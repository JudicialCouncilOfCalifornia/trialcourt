CKEditor InsertHTML
===================

INTRODUCTION
------------

This module integrates the [inserthtml4x](
https://ckeditor.com/cke4/addon/inserthtml4x) CKEditor plugin for Drupal 8.

It provides a simple dialog to insert HTML directly from the editing area into
the source code at the point selected in the editor.

A common use case would be to assist content administrators who may need to
paste code snippets or embed code from third party services, but are not
comfortable working directly in source code.


REQUIREMENTS
------------

* CKEditor Module (Core)


INSTALLATION
------------

### Install via Composer (recommended)

If you use Composer to manage dependencies, edit composer.json as follows.

* Run `composer require --prefer-dist composer/installers` to ensure you have
the composer/installers package. This facilitates installation into directories
other than vendor using Composer.

* In composer.json, make sure the "installer-paths" section in "extras" has an
entry for `type:drupal-library`. For example:

```json
{
  "libraries/{$name}": ["type:drupal-library"]
}
```

* Add the following to the "repositories" section of composer.json:

```json
{
  "type": "package",
  "package": {
    "name": "ckeditor/inserthtml4x",
    "version": "2.0.0",
    "type": "drupal-library",
    "extra": {
      "installer-name": "ckeditor/plugins/inserthtml4x"
    },
    "dist": {
      "url": "https://download.ckeditor.com/inserthtml4x/releases/inserthtml4x_2.0_0.zip",
      "type": "zip"
    }
  }
}
```

* Run `composer require 'ckeditor/inserthtml4x:2.0.0'` to download the plugin.

* Run `composer require 'drupal/ckeditor_inserthtml:^1.0.0'` to download the
CKEditor InsertHTML module, and enable it [as per usual](
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).


### Install Manually

* Download the [inserthtml4x](https://ckeditor.com/cke4/addon/inserthtml4x)
CKEditor plugin.

* Extract and place the plugin contents in the following directory:
`/libraries/ckeditor/plugins/inserthtml4x/`.

* Install the CKEditor InsertHTML module [as per usual](
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).


CONFIGURATION
-------------

* Go to 'Text formats and editors' (`admin/config/content/formats`).

* Click 'Configure' for any text format using CKEditor as the text editor.

* Configure your CKEditor toolbar to include the Insert HTML button.


TROUBLESHOOTING
---------------

* If 'Limit allowed HTML tags and correct faulty HTML' is enabled, note that
code entered via the Insert HTML dialog will be filtered according to the
allowed tags. If code is disappearing, it's most likely due to the allowed tags
configuration.

* This project only handles the bridge between inserthtml4x and Drupal. For
support of the plugin itself, please use their [project page](
https://github.com/gpickin/ckeditor-inserthtml).


MAINTAINERS
-----------
Current maintainers:

 * Corey Eiseman ([toegristle](https://www.drupal.org/u/toegristle))
