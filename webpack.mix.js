let mix = require('laravel-mix');
require('laravel-mix-merge-manifest');
mix.mergeManifest();
mix.js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css');