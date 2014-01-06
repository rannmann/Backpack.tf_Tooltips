/*
 *   TF2 Tooltips
 *   Copyright (C) 2012-2013  Jake "rannmann" Forrester
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
jQuery(document).ready(function()
{
    jQuery('.tfPrice').each(function($) // Pass $ just in case $.noConflict was declared.
    {

        jQuery(this).qtip({
            content: {
                text: 'Loading...',
                ajax: {
                     url: './tftips.php', // Grab price data from script...
                     type: 'POST',
                     data: {
                        itemname: jQuery(this).html() // .. and send item name
                    }
                }
            },
            position: {
                my: 'top left',
                target: 'mouse',
                    viewport: jQuery(window), // Keep it on-screen at all times if possible
                    adjust: {
                        x: 10,  y: 10
                    }
                },
                hide: {
                    fixed: true // Helps to prevent the tooltip from hiding ocassionally when tracking!
                },
                style: {
                    classes: 'ui-tooltip-shadow ui-tooltip-tipsy'
                }
            });

        // Add the item quality classes
        jQuery(this).addClass(function()
        {
            var item = jQuery(this).html();
            var quality;
            if (item.match(/^V. /i) || item.match(/^Vintage /i)) {
                // Curse whoever decided putting "Vintage" in an actual item name was a good idea
                if (item.match(/^Vintage Merryweather/i) || item.match(/^Vintage Tyrolean/i)) {
                    quality = 'unique';
                } else {
                    quality = "vintage";
                }
            }
            else if (item.match(/^G. /i) || item.match(/^Genuine /i)) {
                quality = "genuine";
            }
            else if (item.match(/^S. /i) || item.match(/^Strange /i)) {
                if (item.match(/^Strange Part/i) || item.match(/^Strange Bacon/i)) {
                    quality = 'unique';
                } else {
                quality = "strange";
                }
            }
            else if (item.match(/^Collect[oe]r'?s? /i)) {
                quality = "collectors";
            }
            else if (item.match(/^H. /i) || item.match(/^Haunted /i)) {
                quality = "haunted";
            }
            else if (item.match(/^U. /i) || item.match(/^Unusual /i)) {
                quality = "unusual";
            }
            else if (item.match(/^C. /i) || item.match(/^Community /i)) {
                quality = "community";
            }
            else if (item.match(/^Valve /i)) {
                quality = "valve";
            }
            else if (item.match(/^Self-Made /i)) {
                quality = "self-made";
            }
            else {
                quality = "unique";
            }
            return quality;
        });
    });
});