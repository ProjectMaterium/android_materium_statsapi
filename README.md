# stats.materium.eu.org
This is the stats website we use to track the number of our devices :)


# Setup

## Database
Anything MySQL compatible

Tested with:
- MariaDB 10.4.22

```sql
CREATE TABLE `DATABASENAME`.`device` ( `device_hash` TEXT NOT NULL , `device_name` TEXT NOT NULL , `device_version` TEXT NOT NULL , `device_country` TEXT NOT NULL , `device_carrier` TEXT NOT NULL , `device_carrier_id` TEXT NOT NULL , UNIQUE `device_hash` (`device_hash`)) ENGINE = InnoDB;
```

## Webserver
Any php compatible webserver

Tested with:
- php 7.3.33

# API v1
The API is reachable at https://stats.materium.eu.org/api/v1/

```
https://stats.materium.eu.org/api/v1/getDevice/cucmber
https://stats.materium.eu.org/api/v1/COMMAND/ARGUMENT
```

The whole url is parsed case non-sensitive so don't worry we also accept requests like this
```
https://stats.materium.eu.org/api/v1/GeTdEVICe/CuCUMbeR
```

## Requests/Commands

- getAllDevices
    - retuns a list of all devices sorted alphabetically
    - json format: ```["cucumber","cedric","sweet"]```

<br/>

- <a id="COMMAND_V1_GETTOPDEVICES"></a>getTopDevices
    - see config: [LIMIT_TOP_GETTOPDEVICES](#CONFIG_LIMIT_GETTOPDEVICES)
    - returns a list of top X devices with the most installations
    - json format: ```["cucumber","cedric","sweet"]```

<br/>

- <a id="COMMAND_V1_GETTOPCOUNTRIES"></a>getTopCountries
    - see config: [LIMIT_TOP_GETTOPCOUNTRIES](#CONFIG_LIMIT_GETTOPDEVICES)
    - returns a list of top X countries with the most installations
    - json format: ```["DE","EN","US","CZ"]```

<br/>

- <a id="COMMAND_V1_GETDEVICE"></a>getDevice
    - see config: [LIMIT_GETDEVICE_TOP_COUNTRIES](#CONFIG_LIMIT_GETDEVICE_TOP_COUNTRIES) [LIMIT_GETDEVICE_TOP_VERSIONS](#CONFIG_LIMIT_GETDEVICE_TOP_VERSIONS)
    - arguments: api/v2/getDevice/DEVICE_CODENAME
    - returns the following data about the device: codename, installations, top X countries
    - json format: ```{"name":"sweet","installations":1337,"top_countries":{"EN":200,"CZ":159,"US":44,"IT":10},"top_versions":{"17.1-20170101-NIGHTLY-sweet":20,"18.1-20180101-NIGHTLY-sweet":4,"19.0-20220101-OFFICIAL-sweet":1}}```

<br/>

- registerDevice
    - registers a new device or updates data from a device in our database
    - Request type: POST
    - expected json format: ```{"device_hash": "iuhashui2879sda23414sdada", "device_name": "cucumber", "device_version": "14.1-20170101-NIGHTLY-cucumber", "device_country": "DE", "device_carrier": "SCAM MOBILE LTD", "device_carrier_id": "1337"}```

## Config

- <a id="CONFIG_LIMIT_GETTOPDEVICES"></a>LIMIT_GETTOPDEVICES
    - takes an integer
    - sets the limit, how much devices will be shown in the top list of the [getTopDevices](#COMMAND_V1_GETTOPDEVICES) command

<br/>

- <a id="CONFIG_LIMIT_GETTOPCOUNTRIES"></a>LIMIT_GETTOPCOUNTRIES
    - takes an integer
    - sets the limit, how much countries will be shown in the top list of the [getTopCountries](#COMMAND_V1_GETTOPCOUNTRIES) command

<br/>

- <a id="CONFIG_LIMIT_GETDEVICE_TOP_COUNTRIES"></a>CONFIG_LIMIT_GETDEVICE_TOP_COUNTRIES
    - takes an integer
    - sets the limit, how much countries will be shown in the top countries list of the [getDevice](#COMMAND_V1_GETDEVICE) command

<br/>

- <a id="CONFIG_LIMIT_GETDEVICE_TOP_VERSIONS"></a>CONFIG_LIMIT_GETDEVICE_TOP_VERSIONS
    - takes an integer
    - sets the limit, how much countries will be shown in the top versions list of the [getDevice](#COMMAND_V1_GETDEVICE) command
