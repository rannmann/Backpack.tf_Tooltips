Backpack.tf Tooltips
=========

by Jake "rannmann" Forrester

This library provides Team Fortress 2 item tooltips to any item, based off a name search.  Tooltips include the following data

* Image of item
* Item Name
* Item quality color (both in tooltip, and replaced within the span tags)
* Effect name if applicable
* Crate series number if applicable
* Item description
* Backpack.tf estimated value
* Holiday restriction information

[Here is an example page which is included with this repo](http://dev.rannmann.com/Backpack.tf_Tooltips/).



What You Get
-------
* PHP cron to retrieve item schema from steampowered.com and create/update MySQL tables with data
* PHP cron to retrieve price data from backpack.tf and create/update MySQL tables with data
* Javascript mouseover tooltips with item data (fetched via PHP & AJAX)
* 128x128 and 512x512 images of all current paint cans
* A sample page

Usage
-------
Wrap an item name in a *&lt;span class="tfPrice"&gt;* tag, and the rest is taken care of for you.  Vintage, strange, unusual, etc. items will display as the proper item color, and all items will have [brackets] around them.

####Examples:

    <span class="tfPrice">Vintage Gunslinger</span> <-- Works!
    <span class="tfPrice">V. Gunslinger</span> <-- Also works!
    <span class="tfPrice">V Gunslinger</span> <-- Doesn't work.  Needs either "V." or "Vintage"

    <span class="tfPrice">Crate #42</span> <-- Works!
    <span class="tfPrice">Unusual Carouser's Capotain (Circling Heart)</span> <-- Works!
    <span class="tfPrice">U. Circling Heart Carouser's Capotain</span> <-- Also works!
    <span class="tfPrice">Circling Heart Carouser's Capotain</span> <-- Doesn't work.  Must prefix with "U." or "Unusual"

Config
-------
The _config.php_ file is pretty self-explanatory.  There is, however, one more change that may need to be made.  The javascript file _tftips.js_ uses the following line:

    url: './tftips.php', // Grab price data from script...

Since this path is relative, it may or may not work for you.  __If you install in a subdirectory, don't forget to edit this line!__

Credit
-------
Built with [jQuery](http://jquery.com) and [qTip2](http://craigsworks.com/projects/qtip2).

Data provided by [backpack.tf](http://backpack.tf/api) and [steampowered.com](http://steamcommunity.com/dev).

A special thanks to the [DrunkenBombers](http://www.drunkenbombers.com) community for helping me debug.

Questions?  Comments?  Open an issue or send me an email at [rannmann@rannmann.com](mailto:rannmann@rannmann.com).  I'd love to hear any feedback, good or bad!


Version History
----------------

### 1.1.0

* Item parsing no longer requires a price to be set on backpack.tf.  Will show "N/A" if no price found
* Improved item lookup efficiency, especially with Unusuals
* Refactored searching to be more readable and editable

The way searching works has changed considerably, so _please_ open a ticket if an item isn't displaying correctly anymore.


### 1.0.0

* Rewrote backpack.tf cronjob to be compatible with v4 of the API.  Database table structure changed a bit due to this.
    * Added four new columns
        * `currency` - The currency the item's price is in (ex: metal, keys, earbuds, usd)
        * `value_raw` -  The item's value in metal without rounding.  If a price range exists, this is the average between the high and low value.
        * `tradable` - An enum denoting "Tradable" or "Non-Tradable"
        * `craftable` - An enum denoting "Craftable" or "Non-Craftable"
    * Changed `value` column units to `currency` rather than the default _refined metal_


### 0.2.4

Added Collector's items


### 0.2.3

Added support for jQuery's noConflict mode.  This means tooltips can work on IPB now.

##### CHANGELOG

* `js/tftips.js` - Changed instances of $ to jQuery, and pass $ as a parameter to qTip2


### 0.2.2

Bug fixes

##### CHANGELOG

* `tftips.php` - Fixed Strange Bacon Grease showing up as a strange-quality item
* `tftips.php` - Fixed Strange Parts showing up as strange-quality items
* `js/tftips.js` - Fixed display of Strange Parts and Strange Bacon Grease
* `index.html` - Added examples of non-strage strange-prefixed items


### 0.2.1

##### CHANGELOG

* `tftips.php` - Fixed Scorching Flames unusuals returning Burning Flames
* Marginally increased efficiency of unusual searching


### 0.2.0

Mostly bug fixes and some upgrades to support jQuery 1.9.0+.

##### CHANGELOG

* `backpack_db.php` - New error checking added to relay [backpack.tf](http://backpack.tf/api) API errors.
* Fixed unusual effect "Orbiting Fire" from always attempting to find hats with "Eerie Orbiting Fire".
* Fixed items with "Vintage" as part of the unique item name only showing vintage quality of items.
* Fixed some line-wrap issues with tooltips
* `index.html` - Updated example page to describe some features, and made it look a lot prettier.
* `jquery.qtip.min.js` - Updated to support newer versions of jQuery.
* Changed default width on tooltips to 410px
* Changed default opacity of tooltips so they're more readable
* Added the word "Series" before crate numbers
* Added proper GPL headers.

##### UPGRADING FROM PREVIOUS VERSIONS

The following files should be updated:

* `cron/backpack_db.php` - For error handling
* `css/tftips.css` - For new formatting
* `js/jquery.qtip.min.js` - New qTip version to support jQuery versions 1.9.0+
* `js/tftips.js` - Fix vintage parsing on Vintage Merryweather and Vintage Tyrolean (which aren't vintage-quality!)
* `tftips.php` - Many bug fixes.


### 0.1.0 

First release.
