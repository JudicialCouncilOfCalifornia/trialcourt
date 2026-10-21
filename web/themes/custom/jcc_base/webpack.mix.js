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
const glob = require("glob");

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

mix.sass("src/sass/jcc_base.style.scss", "css", nodeSassOptions).options({
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
mix
  .js("src/js/jcc_base.script.js", "js")
  .js("src/js/datefinder.js", "js")
  .js("src/js/jump-nav.js", "js")
  .js("src/js/accordion.js", "js")
  .js("src/js/read-more.js", "js");

/*
 |--------------------------------------------------------------------------
 | CKEditor 5
 |--------------------------------------------------------------------------
 | The CSS listed in ckeditor_stylesheets (CKEditor 4), scoped to the editor.
 */
mix.then(() =>
  require("./scripts/ckeditor5-css")(
    [
      "assets/css/jcc_base.style.css",
      "src/css/ckeditor-custom-styles.css",
      "../../../libraries/courtyard-artifact/1.x/public/css/styles-trial.min.css",
    ],
    "assets/css/ckeditor5.css"
  )
);
