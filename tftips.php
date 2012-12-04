<?php

include_once('config.php');

$link = mysql_connect($server, $dbuser, $dbpass) or die(mysql_error());
mysql_select_db($database) or die(mysql_error());

$itemname = mysql_real_escape_string($_POST['itemname']); // Supplied from javascript

$colors = array( // Hex colors for name display
    1  => '#4D7455', // Genuine
    3  => '#476291', // Vintage
    5  => '#8650AC', // Unusual
    6  => '#FFD700', // Unique
    7  => '#70B04A', // Community
    8  => '#A50F79', // Valve
    9  => '#70B04A', // Self-Made
    11 => '#CF6A32', // Strange
    13 => '#38F3AB' // Haunted
);

$removes = array( // Regular expressions to remove when performing the query
    '/^The /iu',
    '/^Vintage /iu',
    '/^V. /iu',
    '/^Genuine /iu',
    '/^G. /iu',
    '/^Strange (?!Part)/iu',
    '/^S. /iu',
    '/^Haunted /iu',
    '/^H. /iu',
    '/^Unusual /iu',
    '/^U. /iu',
    '/^Community /iu',
    '/^C. /iu',
    '/^Valve /iu',
    '/^Self-Made /iu'
);

/* Given an item quality, determines if item is tradable */
function isTradable($quality) {
  switch ($quality) {
    case '7': // Community
    case '8': // Valve
    case '9': // Self-Made
      return false;
    
    default:
      return true;
  }
}

/* Set the quality of the item */
if (preg_match('/^Vintage /iu', $itemname) || preg_match('/^V. /iu', $itemname)) { $quality = 3; } // Vintage
elseif (preg_match('/^Genuine /iu', $itemname) || preg_match('/^G. /iu', $itemname)) { $quality = 1; } // Genuine
elseif (preg_match('/^Strange (?!Part)/iu', $itemname) || preg_match('/^S. /iu', $itemname)) { $quality = 11; } // Strange
elseif (preg_match('/^Haunted /iu', $itemname) || preg_match('/^H. /iu', $itemname)) { $quality = 13; } // Haunted
elseif (preg_match('/^Unusual /iu', $itemname) || preg_match('/^U. /iu', $itemname)) { $quality = 5; } // Unusual
elseif (preg_match('/^Community /iu', $itemname) || preg_match('/^C. /iu', $itemname)) { $quality = 7; } // Community
elseif (preg_match('/^Valve /iu', $itemname)) { $quality = 8; } // Valve (developer)
elseif (preg_match('/^Self-Made /iu', $itemname)) { $quality = 9; } // Self-Made
else { $quality = 6; } // Assume everything else is "Unique", as "Normal" only applies to stock items.

/* Check for effects */
if ($quality  == 5) {
  /* Bear with me... this is super hacksy because I'm not a DBA.
   * This loops through each word in a sentence
   * performing a wildcard "LIKE" match and incrementing 
   * a counter.  The results are sorted by the number of matches
   * and the one with the greatest matches is returned.
   * There's no standardized way of traders using effect names,
   * so this will just "magically" figure it out. */
  $q = 'SELECT * , ';
  foreach(explode(' ',$itemname) as $word) {
    $q .= 'IF( name LIKE "%'.preg_replace("/[^A-Za-z0-9 ]/", '', $word).'%", 1, 0 ) + ';
  }
  $q = substr($q,0,-2); // Remove the last "+ "
  $q .= 'AS found
  FROM  `particle_schema` 
  ORDER BY found DESC 
  LIMIT 1';
  $q = mysql_query($q) or die(mysql_error());
  $q = mysql_fetch_row($q);
  $effect = array($q[0], $q[1]); // Effect ID, Effect Name
}

/* Special case for crates, because backpack.tf has mean json */
elseif (preg_match('/Crate /iu',$itemname) && !preg_match('/Key/iu',$itemname)) {
  foreach(explode(' ',$itemname) as $word) {
    if (preg_match('/^\#/',$word)) {
      $effect = array(substr($word,1),$word); // 41, #41
      break;
    }
  }
  $itemname = 'Mann Co. Supply Crate';
}


$itemname = preg_replace($removes,'',$itemname); // remove all prefixes


if ($quality == 5) {
    /*
    This is pretty confusing, so here's what it's creating.  This had to be done because of hat effects as noted above.
    SELECT item.item_name, item.proper_name, item.image_url, item.item_description, item.holiday_restriction, bp.quality, bp.effect, bp.value, 
    IF( item.item_name LIKE "%Unusual%", 1, 0 ) + 
    IF( item.item_name LIKE "%Stout%", 1, 0 ) + 
    IF( item.item_name LIKE "%Shako%", 1, 0 ) + 
    IF( item.item_name LIKE "%Searing%", 1, 0 ) + 
    IF( item.item_name LIKE "%Flames%", 1, 0 ) AS found
    FROM  `item_schema` as item ,  `backpack` as bp
    WHERE item.defindex = bp.defindex
    AND bp.quality = 5
    AND bp.effect = 15
    ORDER BY found DESC
    */
    $q = 'SELECT item.item_name, item.proper_name, item.image_url, item.item_description, item.holiday_restriction, bp.quality, bp.effect, bp.value, ';
    foreach(explode(' ',$itemname) as $word) {
        $q .= 'IF( item.item_name LIKE "%'.$word.'%", 1, 0 ) + ';
    }
    $q = substr($q,0,-2); // Remove the last "+ "
    $q .= 'AS found
    FROM  `item_schema` as item ,  `backpack` as bp
    WHERE item.defindex = bp.defindex
    AND bp.quality = '.$quality;
    if (isset($effect)) {
       $q .= ' AND bp.effect = '.$effect[0];
   }
   $q .= ' ORDER BY found DESC
   LIMIT 1';
}
else { /* Makes most searches (non-unusual quality) faster than the above */
   $q = 'SELECT item.item_name, item.proper_name, item.image_url, item.item_description, item.holiday_restriction, bp.quality, bp.effect, bp.value, item.rgb1
   FROM  `item_schema` as item ,  `backpack` as bp
   WHERE item.item_name LIKE "%'.$itemname.'%"
   AND item.defindex = bp.defindex ';
   if (isTradable($quality)) {
    $q .= 'AND bp.quality = '.$quality;
   }
   if (isset($effect)) {
       $q .= ' AND bp.effect = '.$effect[0];
   }
   // Length is used so things like "Jarate" don't return "Emerald Jarate" on accident.  More specificity is better.
   $q .= ' ORDER BY LENGTH(item.item_name) ASC
   LIMIT 1';
}

$q = mysql_query($q) or die(mysql_error());
$q = mysql_fetch_row($q);

if (!$q[0]) { // If the item name could not be found, exit with error message.
    die('Error: Item not found!'); 
}
else {

}

// With the queries done, we can mess with the effect variable
// for community weapons.
if (!$effect && $quality == 7) {
  $effect[1] = "Community Sparkle";
}


echo '
<table style="width:100%">
    <tbody>
        <tr>
            <td style="width:30%">
                <img src="';
                if ( ($q[2] == "http://media.steampowered.com/apps/440/icons/teampaint.1a4edd3437656c11c51bf790de36f84689375217.png") || // Team paint URL
                     ($q[2] == "http://media.steampowered.com/apps/440/icons/paintcan.9046edf23b64960a4084dad29d05d2c902feec78.png") ) { // Regular paint URL
                     echo $paintdir.'Paint_Can_'.strtoupper($q[8]).'.png'; // Paint_Can_FF69B4.png, for example
                }
                else { echo $q[2]; } // Otherwise just use the steam-given URL.
                echo '">
            </td>
            <td style="width:70%">
                <span class="itemName">
                    <span style="color:'.$colors[$quality].'">';
                        if ($q[1]) { echo 'The '; } // ProperName -> "The "
                        echo $q[0].'
                    </span>
                </span>
        <span class="effect">
                    '.$effect[1].'
                </span>
                <span class="description">
                    '.$q[3].'
                </span>
                <span class="value">';
                if (isTradable($quality)) {
                  echo 'Suggested Price: '.$q[7].' ref';
                }
                else {
                  echo 'Untradable';
                }
                echo '</span>
                <span class="restriction">
                    '.str_replace('_',' ',$q[4]).'
                </span>
            </td>
        </tr>
    </tbody>
</table>';
?>