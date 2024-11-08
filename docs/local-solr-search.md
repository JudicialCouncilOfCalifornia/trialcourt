# Search

## Enabling Solr Search in your local environment

1. Create a .lando.local.yml in the root directory.  Add this content:
```
config:
  xdebug: true
proxy:
  solr:
    - "solr.search.lndo.site:8983"
services:
  solr:
    type: solr:8
    core: drupal
    portforward: true
    config:
      dir: web/modules/contrib/search_api_solr/jump-start/solr8/config-set

```

2. Create or modify the web/sites/<SITE>/settings.local.php.  Add these lines:
```
$config['search_api.server.pantheon_solr8']['backend_config']['connector'] = 'standard';
$config['search_api.server.pantheon_solr8']['backend_config']['connector_config']['host'] = 'solr';
$config['search_api.server.pantheon_solr8']['backend_config']['connector_config']['core'] = 'drupal';
$config['search_api.server.pantheon_solr8']['status'] = true;
$config['search_api.index.index']['status'] = true;
```

3. Run these commands:
```
% lando destroy -y
% lando rebuild -y
```

## Disabling Solr Search in your local environment.

1. Add these lines to web/sites/<SITE>/settings.local.php:
```
$config['search_api.server.pantheon_solr8']['status'] = FALSE;
$config['search_api.index.index']['status'] = FALSE;
```