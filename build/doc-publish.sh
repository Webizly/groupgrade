#!/bin/bash
git pull;

# Build the documentation
apigen -c build/apigen.neon

# Move to a folder outside GIT
if [ -d "../groupgrade-docs" ]; then
  rmdir ../groupgrade-docs;
fi

mv build/docs ../groupgrade-docs

git checkout gh-pages
rm -rf *

mv ../groupgrade-docs/* ./
rm -rf ../groupgrade-docs

git commit -a -m "Importing documentation"
git push origin gh-pages
