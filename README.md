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

Normally I'd provide an example page here, but until I set one up, [here is an example use case](http://www.drunkenbombers.com/forums/showthread.php?983-Forum-TF2-Item-Links).



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