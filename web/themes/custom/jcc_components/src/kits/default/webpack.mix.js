/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your application. See https://github.com/JeffreyWay/laravel-mix.
 |
 */
const mix = require("laravel-mix");

/*
 |--------------------------------------------------------------------------
 | Configuration
 |--------------------------------------------------------------------------
 */
mix.setPublicPath("assets").disableNotifications();

/*
 |--------------------------------------------------------------------------
 | Browsersync
 |--------------------------------------------------------------------------
 */

// Note: To customize the proxy, see docs in README.md.
mix.browserSync({
  proxy: process.env.MIX_PROXY ? process.env.MIX_PROXY : "https://tc.lndo.site",
  files: ["assets/js/**/*.js", "assets/css/**/*.css"],
  stream: true,
  watch: true,
});

/*
 |--------------------------------------------------------------------------
 | SASS
 |--------------------------------------------------------------------------
 */

// Node Sass Options: https://github.com/sass/node-sass#options
const nodeSassOptions = {
  includePaths: ["node_modules", "../../../libraries"],
};

mix.webpackConfig({
  module: {
    rules: [
      {
        test: /\.scss$/,
        loader: "import-glob-loader",
      },
    ],
  },
});

mix
  .sass(
    "src/sass/JCC_COMPONENTS_SUBTHEME_MACHINE_NAME.style.scss",
    "css",
    nodeSassOptions
  )
  .options({
    processCssUrls: false,
    autoprefixer: {
      enabled: true,
      options: {
        grid: true,
        overrideBrowserslist: ["last 2 versions", ">= 1%", "ie >= 11"],
      },
    },
  });

/*
 |--------------------------------------------------------------------------
 | CSS
 |--------------------------------------------------------------------------
 */
mix.styles(
  ["node_modules/flatpickr/dist/flatpickr.min.css"],
  "assets/css/flatpickr.min.css"
);

/*
 |--------------------------------------------------------------------------
 | JS
 |--------------------------------------------------------------------------
 */
mix.js("src/js/JCC_COMPONENTS_SUBTHEME_MACHINE_NAME.script.js", "js");
