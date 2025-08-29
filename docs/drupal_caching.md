# Drupal Caching Notes (CDN + Dynamic Page Cache + Big Pipe)

## Current Cache Settings
 - CDN in front of site: caches pages according to Cache-Control: max-age.
 - Internal Page Cache (IPC): disabled (to avoid conflicts with Varnish/CDN).
 - Dynamic Page Cache (DPC): enabled (caches render arrays for authenticated and anonymous users).
 - Big Pipe: enabled (streams page content in segments, replacing placeholders asynchronously).

## Caching Implications

  1. CDN & Varnish dominate cache expiry
    - With IPC disabled, Drupal doesn’t store full page responses.
    - CDN and Varnish serve cached pages until `Cache-Control: max-age` expires.
    - Even if Drupal calculates per-entity max-age (e.g., via cache metadata), this does not bubble up to override the global `Cache-Control: max-age` set on the full response when Big Pipe is enabled.
  2. Dynamic Page Cache still useful
     - DPC ensures render arrays (fragments) are cached and reused inside Drupal.
     - However, CDN and Varnish will not see those fragment TTLs — they only respect the outer page response headers.
  3. Big Pipe interactions
     - Big Pipe breaks pages into placeholders and streams them in later.
     - This improves perceived performance for users but forces Drupal to send a single, consistent max-age for the whole response.
     - Node-specific max-age (set in preprocess or cache metadata) does not bubble up to influence CDN/varnish TTL when Big Pipe is active.

## Practical Limitation

If you want a node to re-render at a specific interval (e.g., controlled in hook_preprocess_node()), you are effectively constrained by:
 - The global Cache-Control: max-age header of the page response.
 - The CDN and Varnish will serve cached pages until that global max-age expires, regardless of per-node cache metadata.

**Result:** The shortest refresh interval is the global max-age — you cannot force per-node TTLs through CDN + Big Pipe.

**Option:** Disable Big Pipe. Without Big Pipe, the node's max-age will bubble up to the page cache level and the lowest max-age value of all bubbled render arrays will dictate the page max-age for the Cache Control header. Thus, making the page expire at the lower max-age of either the node render array, or the global max-age, for example. If there is anything else rendered on the page with an even lower max-age, that will set the max-age in the CDN.

## Summary

 - Granular node TTLs don’t reach the CDN with Big Pipe.
 - All content respects the global page max-age.
 - Workarounds:
   - Manually purge CDN/Varnish when specific content updates.
   - Lower the global max-age (with performance tradeoffs).
   - Use surrogate keys or cache tags with explicit invalidations if supported by your CDN.

## Testing and Debugging Tools

### Lando Varnish Config

If you wish to test caching behavior locally with the experience of a CDN you can use the Varnish service for a reverse proxy.

.lando.yml or .local.lando.yml (preferred)
```
...
services:
  varnish:
    type: varnish:6
    backends:
     - appserver
    backend_port: 80
    overrides:
      environment:
        # So you can manually purge varnish for testing.
        VARNISH_ALLOW_UNRESTRICTED_PURGE: 1
```

Don't forget to `lando rebuild` after changes to the .lando.yml file.

### Commands for Testing

NOTE: Due to our complex multisite configuration we need to specifiy the host to query along with our call to the varnish service at localhost.

Use `lando info` to find the URL for the varnish service.

i.e. `'http://localhost:59936'`

NOTE: The `:PORT` number will change each time the service is restarted so if you're having problems connecting to varnish, make sure you have the current `:PORT` in your URL.

NOTE: To avoid complexity in varnish configuration use `http://` protocol, not `https://`.

#### Query A Page Via Varnish

`curl -s -D - -o /dev/null -H "Host: cjer.lndo.site" http://localhost:59936/course/calvin`

NOTES:
 - `-s Silent mode. Does not show progress bar or errors.`
 - `-D Dumps the header so we can inspect it for cache headers`
 - `-o /dev/null` sends the body to the abyss so it does not show in the results.
 - `-H "Host: cjer.lndo.site" Adds a Host header to tell Varnish which of our many multisites to address.`
 - Finally the Varnish URL to hit, including the Drupal path to the content we want, which is added to the host we send when querying Drupal to refresh the cache.

The output will be the Response Header from the query.

We can make this a little easier to read by focusing on just the response headers we care about for debugging.

```
curl -D - -o /dev/null -H "Host: cjer.lndo.site" \
http://localhost:59936/course/calvin \
| grep -iE "Cache-Control|Age|X-Cache|x-drupal" \
| awk '{ if(length($0)>100) print substr($0,1,100)"..."; else print $0 }'
```

This is the same query piped through grep to show only the headers we want and `awk` to only print the first 100 characters of a line. You might want to remove the `awk` part if you're looking for specific cache tags or context strings.

#### Manually Purge Varnish Cache

You can manually purge a Varnish cache for a specific path with a curl command.

`curl -X PURGE -H "Host: cjer.lndo.site" http://localhost:59936/course/calvin`

You can see this is similar to the previous commands except it's a PURGE request rather than the (default) GET request. This will purge the cache for the specified path.

Be sure to use the correct `:PORT` number.

### Testing With Postman (or other API testing tools)

If using a tool like Postman, the same rules apply. Use the URL for varnish as your address, making sure you have the right `:PORT` number and path to your test content.

Then in the Request Headers add a new Header for Host with the value of the lando multisite you're trying to reach.  i.e. cjer.lndo.site.

The response header is formatted for easy inspection.

### Renderer Config for Debugging Cache

services.local.yml
```
parameters:
  twig.config
    renderer.config:
      debug: true
      required_cache_contexts: [ ]
      auto_placeholder_conditions:
        # max-age: 0
        contexts: [ ]
        tags: [ ]
```
This config will render debug information about the render cache for each render array rendered in the HTML.  This is similar to theme debugging.
```
<!-- START RENDERER -->
<!-- CACHE-HIT: Yes -->
<!-- CACHE TAGS:
   * node_view
   * node:26
   * config:filter.format.minimal
   * config:filter.format.body
   * config:filter.format.snippet
   * user:1
   * config:access_unpublished.settings
   * config:system.site
-->

<!-- CACHE CONTEXTS:
   * url.site
   * user.permissions
   * url.query_args:auHash
   * languages:language_interface
   * url.query_args:key
   * timezone
-->
<!-- CACHE MAX-AGE: -1 -->
<!-- THEME DEBUG -->
<!-- THEME HOOK: 'node' -->
<!-- FILE NAME SUGGESTIONS:
   * node--26--full.html.twig
   * node--26.html.twig
   * node--course--full.html.twig
   x node--course.html.twig
   * node--full.html.twig
   * node.html.twig
-->
<!-- BEGIN OUTPUT from 'themes/custom/jcc_elevated/templates/node/node--course.html.twig' -->
```
