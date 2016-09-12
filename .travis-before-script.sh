#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Add custom modules to drupal build.
cd "$DRUPAL_TI_DRUPAL_DIR"

# Enable main module and submodules.
drush en -y commerce commerce_product commerce_order commerce_checkout

# Turn on PhantomJS for functional Javascript tests
phantomjs --ssl-protocol=any --ignore-ssl-errors=true $DRUPAL_TI_DRUPAL_DIR/vendor/jcalderonzumba/gastonjs/src/Client/main.js 8510 1024 768 2>&1 >> /dev/null &
