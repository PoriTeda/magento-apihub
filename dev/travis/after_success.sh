#!/usr/bin/env bash

# Trigger lambda build
cd  $TRAVIS_BUILD_DIR && cat > ../lambda-parameters << EOL
{
  "coding_no": "$YmdHms"
}
EOL