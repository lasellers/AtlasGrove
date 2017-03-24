# AtlasGrove
## By Lewis Sellers (aka Min)
## Intrafoundation Software

(NOTE: Currently being Developed/Refactored. See CLI commands.)

AtlasGrove takes Tiger/Line shapefiles from the US Census Bureau and renders them into usable images.

Version 2.x of this software is a Symfony 3.x rewrite of older bespoke/framework-less software.

![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot1.png)
![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot2.png)
![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot3.png)
![AtlasGrove](https://github.com/lasellers/AtlasGrove/blob/master/screenshot4.png)

## CLI

This software uses both a CLI and web interface. Do php bin/console list to see complete list. Some examples are:

php bin/console atlasgrove/status
php bin/console atlasgrove/cache
php bin/console atlasgrove/render
php bin/console atlasgrove/download

php bin/console atlasgrove/downloads
php bin/console atlasgrove/dbf
php bin/console atlasgrove/shx
php bin/console atlasgrove/shp

# php bin/console atlasgrove/status
Shows basic settings information.

# php bin/console atlasgrove/download
Downloads Tiger/Line data from the US Census FTP site. You must specify the state number to be downloaded. For example:
    php bin/console atlasgrove/download 47
downloads the data related to Tennessee.
By default all downloaded data is stored out of project/public (and out of vagrant/git/auto-backup paths) one directory above the project down in the cache folder, though there are parameter settings to have it show up in /var/cache. 

# php bin/console atlasgrove/cache
Runs through all downloaded Tiger/Line data and generates a simple intermediate data output file that is used for actual rendering.

    php bin/console atlasgrove/cache --all
or 
    php bin/console atlasgrove/cache
will cache all downloaded data.

You may also specific a single geo region like:
    php bin/console atlasgrove/cache 47
    php bin/console atlasgrove/cache 47001

Optionally the use of the --force flag will cause previously generated data to be overwritten.

Other options are:
    php bin/console atlasgrove/cache --states
    php bin/console atlasgrove/cache --counties

# php bin/console atlasgrove/render
Actually renders data. There are several options here.
    php bin/console atlasgrove/render --all --8k
This for instance, renders all the regions of interest (state and county bounded regions) at 8k resolution. The current resolutions are --vga, --1080p, --4k, --8k and --16k.
You may use the --force option to force regenerating an image even if one already exists. You may also specify a geo region id like:
    php bin/console atlasgrove/render 47 --8k --force
    php bin/console atlasgrove/render 47001 --8k --force
or a bound latitude/longitude region such as:
    php bin/console atlasgrove:render -86,34,-85,35 -v --8k 

    php bin/console atlasgrove/render 47 --roi --8k --force
Adding the --roi flag causes the roi of state 47 to be looked up and then rendered as a bound lat/long region.

The layers that are rendered can be selected by a csv list such as:
    php bin/console atlasgrove:render 47001 --roi --layers=water,road,rail,landmark,area
    php bin/console atlasgrove:render 47001 --roi --layers=area,rail

    php bin/console atlasgrove:render 47 aspect=Width

    php bin/console atlasgrove:render 47 lod=1
    php bin/console atlasgrove:render 47 region=Clip
    php bin/console atlasgrove:render 47 region=Roi
    php bin/console atlasgrove:render 47 width=999 height=888

    php bin/console atlasgrove:render --states
    php bin/console atlasgrove:render --counties

    php bin/console atlasgrove:render test=tn
    php bin/console atlasgrove:render test=us

    php bin/console atlasgrove:render test=tn --steps
    php bin/console atlasgrove:render test=us --steps

    php bin/console atlasgrove:render 0 --roi --layers=area,road,rail
    php bin/console atlasgrove:render --roi --layers=area,road,rail


## Web Interface

Under Construction


Installation

composer update
composer install

php bin/console assets:install


