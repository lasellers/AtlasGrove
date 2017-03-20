# AtlasGrove
## By Lewis Sellers (aka Min)
## Intrafoundation Software

AtlasGrove takes Tiger/Line shapefiles from the US Census Bureau and renders them into usable images.

Version 2.x of this software is a Symfony 3.x rewrite of older bespoke/framework software.

![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot1.png)
![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot2.png)
![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot3.png)
![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot4.png)

## CLI

This software uses both a CLI and web interface. Do php bin/console list to see complete list. Some examples are:

php bin/console atlasgrove/status
php bin/console atlasgrove/cache
php bin/console atlasgrove/render

php bin/console atlasgrove/cache --all
php bin/console atlasgrove/render --all --8k

php bin/console atlasgrove/render 47 --8k --force
php bin/console atlasgrove/render 47001 --8k --force

php bin/console atlasgrove:render --roi=-85,35,-86,34  -v --8k 


## Web Interface

Under Construction


