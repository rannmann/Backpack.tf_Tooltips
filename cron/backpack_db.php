<?php
/*
 *   Backpack.tf Database Importer
 *   For use with v4 of the Backpack.tf API
 *   Copyright (C) 2012-2014  Jake "rannmann" Forrester
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

$time_start = microtime(true);
$query_count = 0;

$link = mysql_connect($server, $dbuser, $dbpass);
mysql_select_db($database);

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

$raw = file_get_data('http://backpack.tf/api/IGetPrices/v4/?key=' . $bptf_api_key . '&compress=1&raw=1') or die('Error connecting');
$prices = json_decode($raw,true);
if ($prices['response']['success'] == 0) {
    die('Error recieved from backpack.tf: ' . $prices['response']['message']);
}
foreach($prices['response']['items'] as $itemname => $obj) { // Ugly, but completes in about 0.2 seconds.
  $defindexes = $obj['defindex'];
  foreach($obj['prices'] as $quality => $tradable) {
    foreach($tradable as $tradable => $craftable) { 
      foreach($craftable as $craftable => $priceindex) { // Priceindex = crate series or unusual effect. 0 otherwise.
        foreach($priceindex as $effect => $stats) {
          foreach($defindexes as $defindex) {
            $prepare[] = array(
              'defindex'    => $defindex,
              'quality'     => $quality,
              'effect'      => $effect,
              'value'       => $stats['value'],
              'last_change' => $stats['difference'], // Last price change
              'last_update' => $stats['last_update'],
              'currency'    => $stats['currency'],
              'value_raw'   => $stats['value_raw'],
              'tradable'    => $tradable, // Tradable/Non-Tradable
              'craftable'   => $craftable // Craftable/Non-Craftable
            );
          }
        }
      }
    }
  }
}
/* Create the temporary table */
$q = "CREATE TABLE IF NOT EXISTS $bp_table_temp (
  `defindex` SMALLINT(5) NOT NULL,
  `quality` int(3) NOT NULL,
  `effect` SMALLINT(4) UNSIGNED NOT NULL,
  `value` FLOAT NOT NULL,
  `last_change` FLOAT NOT NULL,
  `last_update` int(10) unsigned default NULL,
  `currency` varchar(20) default 'metal',
  `value_raw` FLOAT NOT NULL,
  `tradable` ENUM('Non-Tradable','Tradable') NOT NULL,
  `craftable` ENUM('Craftable','Non-Craftable') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
mysql_query($q) or die("Unable to create table: " . mysql_error());
$query_count++;
/* Insert backpack.tf data into temporary table */
$q = "INSERT INTO $bp_table_temp VALUES ";
foreach($prepare as $v) {
  //$q .= "('".$v[0]."','".$v[1]."','".$v[2]."','".$v[3]."','".$v[4]."','".$v[5]."'), ";
  $q .= vsprintf("('%d', '%d', '%d', '%.2f', '%.2f', '%u', '%s', '%.2f', '%s', '%s'), ", $v);
  $item_count++;
}
mysql_query(substr($q,0,-2)) or die(mysql_error());
$query_count++;

/* Insert row into the info table to show last update + USD value of ref */
$q = "CREATE TABLE IF NOT EXISTS $infotable (
  `id` int(10) NOT NULL auto_increment,
  `lastupdate` int(11) NOT NULL,
  `usdvalue` double default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
mysql_query($q) or die(mysql_error());
$query_count++;
$q = "INSERT INTO $infotable (lastupdate, usdvalue) VALUES ('".$prices['response']['current_time']."','".$prices['response']['raw_usd_value']."')";
mysql_query($q) or die(mysql_error());
$query_count++;

/* Drop old table if it exists */
$q = "DROP TABLE IF EXISTS $bp_table";
mysql_query($q) or die(mysql_error());
$query_count++;

/* Rename temporary table to new table */
$q = "RENAME TABLE $bp_table_temp TO $bp_table";
mysql_query($q) or die(mysql_error());
$query_count++;

$time_end = microtime(true);
$time = $time_end - $time_start;

echo "1 download, $item_count prices found, and $query_count queries completed successfully in $time seconds.";

?>
