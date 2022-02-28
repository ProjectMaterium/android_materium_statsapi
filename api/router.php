<?php
// Copyright (c) 2022 Bruno Lange <brvn0>
// VERSION: 1.0
// Tested with
// - php 7.3.33
//
// Simple REST Api Router for Project Materium


// url requests will look like the following:
// https://stats.materium.eu.org/api/v2/getDevice/DEVICE_NAME returns details from that device
// https://stats.materium.eu.org/api/API_VERSION/COMMAND/ARGUMENT
//
// Deprecated: (will eventually be removed soon)
// https://stats.materium.eu.org/api/v1.php

// THE URL IS GETTING PARSED WITHOUT ANY FUCKS ABOUT CASE (commands can be written as lower-, uppercase or something mixed)
// the url is getting split by '/' here in the script so for the last example the splitted url looks like:
// u[1] = 'api' - this is default and will not change
// u[2] = 'v1 - for determing which api version is used (to support legacy code in the future)
// u[3] = 'getDevice' - command for the api to handle
// u[4] = 'DEVICE_NAME' - argument(s) for the command

// util function for printing bad request error
function printBadRequest($responseCode = 400, $text = "Bad request")
{
    http_response_code($responseCode);
    die("<h1>{$responseCode} Bad request</h1>{$text}");
}

// gives an array with strings from the full url; these arrays are the string which were seperated by '/'
// https://www.php.net/explode
$u = explode('/', mb_strtolower($_SERVER['REQUEST_URI']));

// call php depending on the api version
switch ($u[2]) {
    case 'v1':
        // version 1
        require_once 'v1/stats.php';

        // watch out when defining new commands, the url is getting parsed in lowercase!
        switch ($u[3]) {
            case 'registerdevice':
                // register new/old device
                registerDevice();
                break;

            case 'getalldevices':
                // print all devices
                getAllDevices();
                break;

            case 'getdevice':
                // get details of a device as json
                getDevice($u[4]);
                break;

            case 'gettopdevices':
                // get's top x devices with the most installations
                getTopDevices();
                break;

            case 'gettopcountries':
                // get's top x countries with the most installations
                getTopCountries();
                break;

            case '':
                printBadRequest(400, "Missing command!");
                break;

            default:
                printBadRequest(400, "Invalid command!");
                break;
        } // end api command switch
        // close db connection from stats api
        disconnectDb();
        break;
    case '':
        printBadRequest(400,"Hello Human<br/>There is some stuff missing in your request");
        break;
    default:
        printBadRequest(400,"Bad API version!");
        break;
} // end api version switch
