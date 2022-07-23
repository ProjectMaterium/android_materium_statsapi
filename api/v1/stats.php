<?php
// Copyright (c) 2022 Bruno Lange <brvn0>
// VERSION: 1.0
// Tested with
// - php 7.3.33
// - MariaDB 10.4.22
//
// Should work with any mysql database

// initialize config
require 'config.php';
$db;
$STATS_CONFIG = getStatsConfig();

// initialize the database connection
function connectDb()
{
  global $STATS_CONFIG;
  // for production remove the statement below cuz it can leak login + password!!!!!!!
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $GLOBALS['db'] = new mysqli($STATS_CONFIG['DATABASE_HOST'], $STATS_CONFIG['DATABASE_USERNAME'], $STATS_CONFIG['DATABASE_PASSWORD'], $STATS_CONFIG['DATABASE_NAME']);
}

// close db connection
function disconnectDb()
{
  global $db;
  $db->close();
}

// pushes the data from a registered device into our databse
//
// exptected json format:
// {"device_hash": "iuhashui2879sda23414sdada", "device_name": "daddy", "device_version": "14.1-20170101-NIGHTLY-daddy", "device_country": "UA", "device_carrier": "Vodaprinter", "device_carrier_id": "69"}
function registerDevice()
{
  global $db;
  connectDb();

  $j = file_get_contents('php://input'); // real input
  //$j = '{"device_hash": "1", "device_name": "cumber", "device_version": "14.1-20170101-NIGHTLY-cucumber", "device_country": "US", "device_carrier": "Carrier", "device_carrier_id": "0"}'; // example data
  $d = json_decode($j); // data array


  if (!(isset($d->device_hash) && isset($d->device_name) && isset($d->device_version) && isset($d->device_country) && isset($d->device_carrier) && isset($d->device_carrier_id))) {
    http_response_code(400);
    die('Bad Request: Some POST data is missing!');
  }

  // defend against mysql injections :D
  // make the userinput mysql compatible (some special characters and letters are replaced with \THATCHARACTER; so for instance ' would become \')
  // but this is not the only safety, it's only here in case all others fail
  foreach ($d as $x => $x_val) {
    $d->$x = $db->real_escape_string($x_val);
  }

  $d->device_version = strstr($d->device_version, '-', true); // remove everything after the first '-'

  // for the sql statement to work we need device_hash as unique key and a databse software that supports the replace statement!!!
  // example command:
  // REPLACE `device` (`device_hash`, `device_name`, `device_version`, `device_version_short`, `device_country`, `device_carrier`, `device_carrier_id`, `timestamp`) values ('1', 'ccucumber', '14.1-20170101-NIGHTLY-cucumber', '14.1', 'US', 'Carrier', '0', '12321321334);
  $stmt = $db->prepare("REPLACE `device` (`device_hash`, `device_name`, `device_version`, `device_version_short`, `device_country`, `device_carrier`, `device_carrier_id`, `timestamp`) values (?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param('ssssss', $d->device_hash, $d->device_name, $d->device_version, strstr($d->device_version, '-', true), $d->device_country, $d->device_carrier, $d->device_carrier_id, time());
  $stmt->execute();

  disconnectDb();
  unset($j, $d, $stmt, $db);
}

// prints a list of all devices
// sorted alphabetically
//
// json format:
// ["cucumber","cumber","daddy"]
function getAllDevices()
{
  global $db;
  connectDb();
  $d = [];
  $r = $db->query("SELECT distinct `device_name` FROM `device`;");
  while ($x = $r->fetch_assoc()) {
    $d[] = $x['device_name'];
  }

  echo json_encode($d);

  mysqli_free_result($r);
  unset($d, $r);
}

// will return some data about the device
// - codename
// - installations
// - top country
//
// json format:
// {"name":"daddy","installations":69,"top_country":"US"}
function getDevice($d)
{
  if (trim($d) == "") {
    printBadRequest(400, 'No device given!');
  }

  global $db;
  connectDb();
  $o = [];

  // get numbers of devices
  $s = $db->prepare("SELECT `device_name` FROM `device` WHERE `device_name` = ?;");
  $x = $db->real_escape_string($d);
  $s->bind_param('s', $x);
  unset($x);
  $s->execute();
  $r = $s->get_result();
  $n = 0;
  while ($rs = mysqli_fetch_assoc($r)) {
    $n++;
  }

  if ($n == 0) {
    echo 'Device not found!';
    printBadRequest(404);
  }

  $o['name'] = $d;
  $o['installations'] = $n;
  unset($s, $n, $r);

  // top country
  $s = $db->prepare("SELECT `device_country`, COUNT(`device_country`) AS `co` FROM `device` WHERE `device_name` = ? GROUP BY `device_country` ORDER BY `co` DESC LIMIT ?;");
  $x = $db->real_escape_string($d);
  if (isset($STATS_CONFIG['LIMIT_GETDEVICE_TOP_COUNTRY'])) {
    $y = $STATS_CONFIG['LIMIT_GETDEVICE_TOP_COUNTRY'];
  } else {
    $y = 10;
  }
  $s->bind_param('si', $x, $y);
  unset($x);
  $s->execute();

  $c = [];
  $r = $s->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($r as $k) {
    $c[$k['device_country']] = $k['co'];
  }
  $o['top_countries'] = $c;

  unset($c, $r, $y, $s, $x);
  // $r = $s->get_result();
  // $o['top_country'] = mysqli_fetch_assoc($r)['device_country'];


  // top version
  $s = $db->prepare("SELECT `device_version_short` AS `version`, COUNT(`version`) AS `vo` FROM `device` WHERE `device_country` = ? GROUP BY `version` ORDER BY `vo` DESC LIMIT ?;");
  $x = $db->real_escape_string($d);
  if (isset($STATS_CONFIG['LIMIT_GETDEVICE_TOP_VERSION'])) {
    $y = $STATS_CONFIG['LIMIT_GETDEVICE_TOP_VERSION'];
  } else {
    $y = 10;
  }
  $s->bind_param('si', $x, $y);
  unset($x);
  $s->execute();

  $c = [];
  $r = $s->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($r as $k) {
    $c[$k['device_version']] = $k['vo'];
  }
  $o['top_versions'] = $c;



  echo json_encode($o);
  unset($o, $n, $s, $r, $c);
}

// get top X (see config: LIMIT_TOP_DEVICES) devices with the most installations
//
// json format:
// ["cumber","daddy","cucumber"]
function getTopDevices()
{
  global $STATS_CONFIG;
  global $db;
  connectDb();

  $s = $db->prepare("SELECT distinct `device_name`, COUNT(`device_name`) AS `do` FROM `device`  GROUP BY `device_name` ORDER BY `do` DESC LIMIT ?;");
  $s->bind_param('s', $STATS_CONFIG['LIMIT_GETTOPDEVICES']);
  unset($x);
  $s->execute();
  $r = $s->get_result();

  $o = [];
  while ($rs = mysqli_fetch_assoc($r)) {
    $o[] = $rs['device_name'];
  }

  echo json_encode($o);
  unset($s, $r, $o);
}

// get top X (see config: LIMIT_TOP_COUNTRIES) countries with the most installations
//
// json format:
// ["DE","EN","US","CZ"]
function getTopCountries()
{
  global $STATS_CONFIG;
  global $db;
  connectDb();

  $s = $db->prepare("SELECT distinct `device_country`, COUNT(`device_country`) AS `do` FROM `device`  GROUP BY `device_country` ORDER BY `do` DESC LIMIT ?;");
  $s->bind_param('s', $STATS_CONFIG['LIMIT_GETTOPCOUNTRIES']);
  unset($x);
  $s->execute();
  $r = $s->get_result();

  $o = [];
  while ($rs = mysqli_fetch_assoc($r)) {
    $o[] = $rs['device_country'];
  }

  echo json_encode($o);
  unset($s, $r, $o);
}

function getCountry($d)
{
  global $STATS_CONFIG;
  global $db;
  connectDb();

  $d = mb_strtoupper($d);

  if (trim($d) == "") {
    printBadRequest(400, 'No device given!');
  }

  // get numbers of devices
  $s = $db->prepare("SELECT `device_country` FROM `device` WHERE `device_country` = ?;");
  $x = $db->real_escape_string($d);
  $s->bind_param('s', $x);
  unset($x);
  $s->execute();
  $r = $s->get_result();
  $n = 0;
  while ($rs = mysqli_fetch_assoc($r)) {
    $n++;
  }
  if ($n == 0) {
    printBadRequest(404, 'Country not found!');
  }

  $o['country'] = $d;
  $o['installations'] = $n;
  unset($s, $r, $n);

  // top devices
  $s = $db->prepare("SELECT `device_name`, COUNT(`device_name`) AS `no` FROM `device` WHERE `device_country` = ? GROUP BY `device_name` ORDER BY `no` DESC LIMIT ?;");
  $x = $db->real_escape_string($d);
  if (isset($STATS_CONFIG['LIMIT_GETCOUNTRY_TOP_DEVICES'])) {
    $y = $STATS_CONFIG['LIMIT_GETCOUNTRY_TOP_DEVICES'];
  } else {
    $y = 10;
  }
  $s->bind_param('si', $x, $y);
  unset($x);
  $s->execute();

  $c = [];
  $r = $s->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($r as $k) {
    $c[$k['device_name']] = $k['no'];
  }

  $o['top_devices'] = $c;
  unset($c, $r, $y, $s, $x);

  // top version
  $s = $db->prepare("SELECT `device_version_short` AS `version`, COUNT(`version`) AS `vo` FROM `device` WHERE `device_country` = ? GROUP BY `version` ORDER BY `vo` DESC LIMIT ?;");
  $x = $db->real_escape_string($d);
  if (isset($STATS_CONFIG['LIMIT_GETCOUNTRY_TOP_VERSIONS'])) {
    $y = $STATS_CONFIG['LIMIT_GETCOUNTRY_TOP_VERSIONS'];
  } else {
    $y = 10;
  }
  $s->bind_param('si', $x, $y);
  unset($x);
  $s->execute();

  $c = [];
  $r = $s->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($r as $k) {
    $c[$k['version']] = $k['vo'];
  }
  $o['top_versions'] = $c;

  echo json_encode($o);

  unset($c, $r, $y, $s, $x);
}
