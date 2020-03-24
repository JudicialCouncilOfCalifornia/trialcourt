# Sass

This directory is processed by the build script. For more information see the [README](../../README.md) in the Atrium theme root.

## How to Add a New Sass Partial

Partials should be used when writing Sass for a new component, page, etc, that should be included in an existing CSS file.

1. Decide what directory it should reside in, and create a new file named with an underscore prefix, e.g. `src/components/widget/_widget.scss`.

2. Open [`atrium.style.scss`](./atrium.style.scss) and import the partial where desired, omitting the underscore and file extension:

    ```diff
      @import 'components/tabs/tabs';
      @import 'components/tabs/tabs-primary';
      @import 'components/tabs/tabs-secondary';
    + @import 'components/widget/widget';
    ```

## How to Add a New Sass File

New Sass files should be used when their contents should be loaded conditionally, as opposed to globally.

1. Create the file in this directory, e.g. `new-file.scss`.

2. Import the base partial at the top to ensure your new file will have access to all the dependencies, variables, mixins, etc.

    ```scss
    @import 'base/base';
    ```

3. Open [`webpack.mix.js`](../../webpack.mix.js) found in the theme root, and add another [`.sass()`](https://laravel.com/docs/6.x/mix#sass) method for the new file, as shown below.

    ```diff
     mix
       .sass('src/sass/atrium.style.scss', 'css', nodeSassOptions)
    +  .sass('src/sass/new-file.scss', 'css', nodeSassOptions)
    ```

4. Restart the build script:

    ```sh
    # ctrl + c to stop if it's already running.
    npm run watch
    ```

5. Create a library definition in `atrium.libraries.yml` and decide how you need to load it within Drupal. See the [documentation](https://www.drupal.org/node/2216195) for more details.
