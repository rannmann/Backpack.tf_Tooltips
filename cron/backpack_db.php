<?php
/*
 *   Backpack.tf Database Importer
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

$time_start = microtime(true);

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


$raw = file_get_data('http://backpack.tf/api/IGetPrices/v2/?format=json&currency=metal') or die('Error connecting');
$prices = json_decode($raw,true);
foreach($prices['response'] as $key => $value) {
  if ($key == 'prices') {
    foreach($value as $item => $quality) {  // $item = defindex
      foreach($quality as $qual => $effect) {
        foreach($effect as $eff => $stats) {
          $prepare[] = array($item,$qual,$eff,$stats['value'],$stats['last_change'],$stats['last_update']);
        }
      }
    }
  }
  elseif ($key == 'error') {
    die('Error recieved from backpack.tf: ' . $value);
  }
  else {
    $info[$key] = $value;
  }
}
/* Create the temporary table */
$q = "CREATE TABLE IF NOT EXISTS $bp_table_temp (
  `defindex` int(6) NOT NULL,
  `quality` int(3) NOT NULL,
  `effect` int(5) default NULL,
  `value` double NOT NULL,
  `last_change` double default NULL,
  `last_update` int(10) unsigned default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
mysql_query($q) or die("Unable to create table: " . mysql_error());

/* Insert backpack.tf data into temporary table */
$q = "INSERT INTO $bp_table_temp (defindex, quality, effect, value, last_change, last_update) VALUES ";
foreach($prepare as $v) {
  $q .= "('".$v[0]."','".$v[1]."','".$v[2]."','".$v[3]."','".$v[4]."','".$v[5]."'), ";
}
mysql_query(substr($q,0,-2)) or die(mysql_error());

/* Insert row into the info table to show last update */
$q = "CREATE TABLE IF NOT EXISTS `info` (
  `id` int(10) NOT NULL auto_increment,
  `lastupdate` int(11) NOT NULL,
  `usdvalue` double default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;";
mysql_query($q) or die(mysql_error());
$q = "INSERT INTO $infotable (lastupdate, usdvalue) VALUES ('".$info['current_time']."','".$info['refined_usd_value']."')";
mysql_query($q) or die(mysql_error());

/* Drop old table if it exists */
$q = "DROP TABLE IF EXISTS $bp_table";
mysql_query($q) or die(mysql_error());

/* Rename temporary table to new table */
$q = "RENAME TABLE $bp_table_temp TO $bp_table";
mysql_query($q) or die(mysql_error());

$time_end = microtime(true);
$time = $time_end - $time_start;

echo "1 download and 6 queries completed successfully in $time seconds.";

?>