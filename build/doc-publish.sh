#!/bin/bash

# Build the documentation
apigen -c build/apigen.neon

# Move to a folder outside GIT
mv build/docs/ ../groupgrade-docs

git checkout gh-pages
rm -rf *

mv ../groupgrade-docs/* ./
