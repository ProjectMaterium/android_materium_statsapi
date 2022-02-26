<!--
Copyright (c) 2022 Bruno Lange <brvn0>
Tested with
- php 7.3.33
- MariaDB 10.4.22

Should work with any mysql database
-->
<?php
  $config = '{
    "database_ip": "localhost",
    "database_name": "EXAMPLE_DATABASE_NAME",
    "database_username": "EXAMPLE_USERNAME",
    "database_password": "EXAMPLE_PW"
  }';

  $j = file_get_contents('php://input'); // real input
  //$j = '{"device_hash": "1", "device_name": "cumber", "device_version": "14.1-20170101-NIGHTLY-cucumber", "device_country": "US", "device_carrier": "Carrier", "device_carrier_id": "0"}'; // example data
  $c = json_decode($config); // config array
  $d = json_decode($j); // data array

  // for production remove the statement below cuz it can leak login + password!!!!!!!
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $db = new mysqli($c->database_ip, $c->database_username, $c->database_password, $c->database_name);

  if (!(isset($d->device_hash) && isset($d->device_name) && isset($d->device_version) && isset($d->device_country) && isset($d->device_carrier) && isset($d->device_carrier_id))) {
    http_response_code(400);
    die('Bad Request: Some POST data is missing!');
  }

  // defend against mysql injections :D
  // make the userinput mysql compatible (some special characters and letters are replaced with \THATCHARACTER; so for instance ' would become \')
  // but this is not the only safety, it's only here in case all others fail
  foreach($d as $x => $x_val) {
    $d->$x = $db->real_escape_string($x_val);
  }

  // for the sql statement to work we need device_hash as unique key and a databse software that supports the replace statement!!!
  // example command:
  // REPLACE `device` (`device_hash`, `device_name`, `device_version`, `device_country`, `device_carrier`, `device_carrier_id`) values ('1', 'ccucumber', '14.1-20170101-NIGHTLY-cucumber', 'US', 'Carrier', '0');
  $stmt = $db->prepare("REPLACE `device` (`device_hash`, `device_name`, `device_version`, `device_country`, `device_carrier`, `device_carrier_id`) values (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param('ssssss', $d->device_hash, $d->device_name, $d->device_version, $d->device_country, $d->device_carrier, $d->device_carrier_id);
  $stmt->execute();
?>
