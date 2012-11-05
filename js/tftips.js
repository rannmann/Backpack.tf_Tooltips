$(document).ready(function()
{
    $('.tfPrice').each(function()
    {

        $(this).qtip({
            content: {
                text: 'Loading...',
                ajax: {
                     url: './tftips.php', // Grab price data from script...
                     type: 'POST',
                     data: {
                        itemname: $(this).html() // .. and send item name
                    }
                }
            },
            position: {
                my: 'top left',
                target: 'mouse',
                    viewport: $(window), // Keep it on-screen at all times if possible
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
        $(this).addClass(function()
        {
            var item = $(this).html();
            var quality;
            if (item.match(/^V. /i) || item.match(/^Vintage /i)) {
                quality = "vintage";
            }
            else if (item.match(/^G. /i) || item.match(/^Genuine /i)) {
                quality = "genuine";
            }
            else if (item.match(/^S. /i) || item.match(/^Strange /i)) {
                quality = "strange";
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