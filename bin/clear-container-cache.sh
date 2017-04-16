#!/bin/sh

# clear project container cache in ezpublish/cache/<env>/
find ./ezpublish/cache/ -maxdepth 2 -name ezpublish*ProjectContainer.php | xargs rm -f
