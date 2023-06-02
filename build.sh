#!/bin/bash

rm -r dist
rm -r public

mkdir -p public/js/ public/css

yarn build-admin
wait $!

mv dist/assets/admin*.js public/js/admin.js
mv dist/assets/admin*.css public/css/admin.css

yarn build-plugin
wait $!

mv dist/assets/plugin*.js public/js/plugin.js
mv dist/assets/plugin*.css public/css/plugin.css
