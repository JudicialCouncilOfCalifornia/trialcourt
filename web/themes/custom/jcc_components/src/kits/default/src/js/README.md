# JavaScript

This directory is processed by the build script. For more information see the [README](../../README.md) in the JCC Components theme root.

## How to Add a New JavaScript Partial

Partials should be used when writing JavaScript for a new component, page, etc, that should be included in an existing JavaScript file.

1. Decide what directory it should reside in, and create a new file, e.g. `src/components/widget.js`.

2. Open [`jcc_components.script.js`](./jcc_components.script.js) and import the partial where desired, omitting file extension:

    ```diff
      import './components/messages';
    + import './components/widget';
    ```

## Adding a New JavaScript File

New JavaScript files should be used when their contents should be loaded conditionally, as opposed to globally.

1. Create the file in this directory, e.g. `new-file.js`.

2. Open [`webpack.mix.js`](../../webpack.mix.js) found in the theme root, and add another [`.js()`](https://laravel.com/docs/6.x/mix#working-with-scripts) method for the new file, as shown below.

```diff
  mix
    .js('src/js/atrium.script.js', 'js')
-   .js('src/js/howtotabs.js', 'js');
+   .js('src/js/howtotabs.js', 'js')
+   .js('src/js/new-file.js', 'js');
```

3. Restart the build script:

    ```sh
    # ctrl + c to stop if it's already running.
    npm run watch
    ```

4. Create a library definition in `jcc_components.libraries.yml` and decide how you need to load it within Drupal. See the [documentation](https://www.drupal.org/node/2216195) for more details.
