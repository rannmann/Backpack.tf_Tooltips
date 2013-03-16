<?php
/*
 *   SteamPowered TF2 Items Database Importer
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

include_once('../config.php');

if (!$steam_api_key) {
  die("Error: API key not set!");
}

$time_start = microtime(true);

$link = mysql_connect($server, $dbuser, $dbpass) or die(mysql_error());
mysql_select_db($database) or die(mysql_error());

function file_get_data($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/* Keep in mind, this raw data is > 3MB */
$raw = file_get_data('http://api.steampowered.com/IEconItems_440/GetSchema/v0001/?key=' . $steam_api_key . '&language=en_US') or die('Error connecting');
$all = json_decode($raw,true); 

if (!$all['result']) {
  die("Error retrieving item schema from steampowered API.");
}

foreach($all['result'] as $key => $value) {
    if ($key == "items") 
    {
        foreach($value as $item) 
        {

            // Paintcans are a special case, so we can get the color information.
            if ($item['tool']['type'] == "paint_can") {
                foreach($item['attributes'] as $attribute) {
                    if ($attribute['class'] == "set_item_tint_rgb" || $attribute['class'] == "set_item_tint_rgb_2") {
                        $rgb[] = str_pad(dechex($attribute['value']), 6, "0", STR_PAD_LEFT); // Hex code in decimal converted to 6-digit hex.
                    }
                }
            }
            
            // defindex, item_type_name, item_name, proper_name, item_description, image_url, item_set, holiday_restriction, color1, color2
            $items[] = array($item['defindex'], $item['item_type_name'], $item['item_name'], $item['proper_name'], 
                     $item['item_description'], $item['image_url'], $item['item_set'], $item['holiday_restriction'], $rgb[0], $rgb[1]);
            unset($rgb);
        }
    }
    elseif ($key == "attribute_controlled_attached_particles") 
    {
        foreach($value as $particle) 
        {
            $particles[] = array($particle['id'], $particle['name']);
        }
    }
    elseif ($key == "item_sets") 
    {
        foreach($value as $set) 
        {
            $sets[] = array($set['item_set'], $set['name']);
        }
    }
    elseif ($key == "attributes") 
    {
        foreach($value as $attribute) 
        {
            $attributes[] = array($attribute['name'], $attribute['defindex'], $attribute['attribute_class'], $attribute['description_string'], $attribute['description_format'], $attribute['effect_type'], 
                        $attribute['hidden'], $attribute['stored_as_integer'], $attribute['min_value'], $attribute['max_value']);
        }
    }
    elseif ($key == "qualities") 
    {
        foreach($value as $k => $v) 
        {
            $qualities[$k][0] = $v;
        }
    }
    elseif ($key == "qualityNames") 
    {
        foreach($value as $k => $v) 
        {
            $qualities[$k][1] = $v;
        }
    }
}

/************
*   ITEMS   *
*************/

/* Create the temporary item table */
$q = "CREATE TABLE IF NOT EXISTS $item_table_temp (
  `defindex` int(10) unsigned NOT NULL default '0',
  `item_type_name` varchar(75) default NULL,
  `item_name` varchar(75) default NULL,
  `proper_name` tinyint(1) default '0',
  `item_description` text,
  `image_url` varchar(150) default NULL,
  `item_set` varchar(60) default NULL,
  `holiday_restriction` varchar(50) default NULL,
  `rgb1` varchar(7) default NULL,
  `rgb2` varchar(7) default NULL,
  PRIMARY KEY  (`defindex`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
mysql_query($q) or die("Unable to create item table: " . mysql_error());

/* Insert item schema into temporary table */
$q = "INSERT INTO $item_table_temp (defindex, item_type_name, item_name, proper_name, item_description, image_url, item_set, holiday_restriction, rgb1, rgb2) VALUES ";
foreach($items as $v) {
  $q .= '("'.$v[0].'","'.$v[1].'","'.mysql_real_escape_string($v[2]).'","'.$v[3].'","'.mysql_real_escape_string($v[4]).'","'.$v[5].'","'.$v[6].'","'.$v[7].'","'.$v[8].'","'.$v[9].'"), ';
}
mysql_query(substr($q,0,-2)) or die(mysql_error());

/* Drop old item table if it exists */
$q = "DROP TABLE IF EXISTS $item_table";
mysql_query($q) or die(mysql_error());

/* Rename temporary item table to new item table */
$q = "RENAME TABLE $item_table_temp TO $item_table";
mysql_query($q) or die(mysql_error());


/****************
*   Particles   *
*****************/

/* Create the temporary particle table */
$q = "CREATE TABLE IF NOT EXISTS $particle_table_temp (
  `id` int(4) NOT NULL,
  `name` varchar(60) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
mysql_query($q) or die("Unable to create particle table: " . mysql_error());

/* Insert particle schema into temporary table */
$q = "INSERT INTO $particle_table_temp (id, name) VALUES ";
foreach($particles as $v) {
  $q .= '("'.$v[0].'","'.mysql_real_escape_string($v[1]).'"), ';
}
mysql_query(substr($q,0,-2)) or die(mysql_error());

/* Drop old particle table if it exists */
$q = "DROP TABLE IF EXISTS $particle_table";
mysql_query($q) or die(mysql_error());

/* Rename temporary particle table to new item table */
$q = "RENAME TABLE $particle_table_temp TO $particle_table";
mysql_query($q) or die(mysql_error());



/****************
*   Item Sets   *
*****************/

/* Create the temporary itemset table */
$q = "CREATE TABLE IF NOT EXISTS $itemsets_table_temp (
`item_set` varchar(70) collate utf8_bin NOT NULL,
  `name` varchar(80) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`item_set`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
mysql_query($q) or die("Unable to create itemset table: " . mysql_error());

/* Insert itemset schema into temporary table */
$q = "INSERT INTO $itemsets_table_temp (item_set, name) VALUES ";
foreach($sets as $v) {
  $q .= '("'.$v[0].'","'.mysql_real_escape_string($v[1]).'"), ';
}
mysql_query(substr($q,0,-2)) or die(mysql_error());

/* Drop old itemset table if it exists */
$q = "DROP TABLE IF EXISTS $itemsets_table";
mysql_query($q) or die(mysql_error());

/* Rename temporary itemset table to new item table */
$q = "RENAME TABLE $itemsets_table_temp TO $itemsets_table";
mysql_query($q) or die(mysql_error());



/*****************
*   Attributes   *
******************/

/* Create the temporary attributes table */
$q = "CREATE TABLE IF NOT EXISTS $attributes_table_temp (
  `name` varchar(50) collate utf8_bin NOT NULL,
  `defindex` int(11) NOT NULL,
  `attribute_class` varchar(50) collate utf8_bin NOT NULL,
  `description_string` text collate utf8_bin,
  `description_format` varchar(50) collate utf8_bin default NULL,
  `effect_type` varchar(20) collate utf8_bin NOT NULL,
  `hidden` tinyint(1) NOT NULL default '0',
  `stored_as_integer` tinyint(1) NOT NULL default '0',
  `min_value` double NOT NULL,
  `max_value` double NOT NULL,
  INDEX (`name`),
  PRIMARY KEY `defindex` (`defindex`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
mysql_query($q) or die("Unable to create attributes table: " . mysql_error());

/* Insert attributes schema into temporary table */
$q = "INSERT INTO $attributes_table_temp (name, defindex, attribute_class, description_string, description_format, effect_type, hidden, stored_as_integer, min_value, max_value) VALUES ";
foreach($attributes as $v) {
  $q .= '("'.mysql_real_escape_string($v[0]).'","'.$v[1].'","'.$v[2].'","'.mysql_real_escape_string($v[3]).'","'.$v[4].'","'.$v[5].'","'.$v[6].'","'.$v[7].'","'.$v[8].'","'.$v[9].'"), ';
}
mysql_query(substr($q,0,-2)) or die(mysql_error());

/* Drop old itemset table if it exists */
$q = "DROP TABLE IF EXISTS $attributes_table";
mysql_query($q) or die(mysql_error());

/* Rename temporary itemset table to new item table */
$q = "RENAME TABLE $attributes_table_temp TO $attributes_table";
mysql_query($q) or die(mysql_error());



/****************
*   Qualities   *
*****************/

/* Create the temporary qualities table */
$q = "CREATE TABLE IF NOT EXISTS $qualities_table_temp (
  `id` int(4) NOT NULL,
  `identifier` varchar(40) collate utf8_bin NOT NULL,
  `name` varchar(40) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
mysql_query($q) or die("Unable to create qualities table: " . mysql_error());

/* Insert qualities schema into temporary table */
$q = "INSERT INTO $qualities_table_temp (id, identifier, name) VALUES ";
foreach($qualities as $k => $v) {
  $q .= '("'.$v[0].'","'.$k.'","'.$v[1].'"), ';
}
mysql_query(substr($q,0,-2)) or die(mysql_error());

/* Drop old itemset table if it exists */
$q = "DROP TABLE IF EXISTS $qualities_table";
mysql_query($q) or die(mysql_error());

/* Rename temporary itemset table to new item table */
$q = "RENAME TABLE $qualities_table_temp TO $qualities_table";
mysql_query($q) or die(mysql_error());



/***** Finishing Stuff ******/
$time_end = microtime(true);
$time = $time_end - $time_start;

echo "1 download and 20 queries completed successfully in $time seconds.";

?>