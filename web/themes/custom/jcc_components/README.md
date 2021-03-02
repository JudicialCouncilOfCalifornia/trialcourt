# JCC Components!

JCC Components is a base theme for Drupal. It's incorporates the pattern library Courtyard developed by the Judicial Council of California.

## Installation

### Requirements

  1. [npm](https://npmjs.org) - Read a guide on how to install Node and npm here.
  2. [Drush 9](https://www.drush.org) - Read a guide on how to install Drush here.
  3. [Components module](https://drupal.org/project/components)


  - Install with composer to your drupal project.
    - `composer require judicial-council-of-california/jcc_components`
  - Enable the base theme in drupal with via the UI or with drush:
    - `drush en jcc_components`
  - Create a new sub theme to work with.
    - `drush --include=/app/web/themes/custom/jcc_components jcc_components:create [subtheme_name]`
  - Enable the sub theme via the UI or with drush:
    - `drush en [subtheme_name]`
  - Set subtheme as default via the UI or with drush:
    - `drush config-set system.theme default [subtheme_name] -y`

## Development

All development should be done on the subtheme created after installation.

The base theme will install the "Courtyard" pattern library components and styles in `web/libraries/courtyard-artifact` and is configured to use those components. See docs for more detailed information and how to use base components in your subtheme.

## Projects using JCC Components

#### [San Luis Obispo: Superior Court of California](https://www.slo.courts.ca.gov)


## Links
* Project Page:   https://github.com/JudicialCouncilOfCalifornia/jcc_components
* Documentation:  https://github.com/JudicialCouncilOfCalifornia/jcc_components/wiki
* Support:        https://www.courts.ca.gov/policyadmin-jc.htm

## License
http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
