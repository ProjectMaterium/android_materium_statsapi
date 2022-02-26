# stats.materium.ml
This is the stats website we use to track the number of our devices :)


# Setup

## Database
Anything MySQL compatible
Tested with: MariaDB 10.4.22

```sql
CREATE TABLE `DATABASENAME`.`device` ( `device_hash` TEXT NOT NULL , `device_name` TEXT NOT NULL , `device_version` TEXT NOT NULL , `device_country` TEXT NOT NULL , `device_carrier` TEXT NOT NULL , `device_carrier_id` TEXT NOT NULL , UNIQUE `device_hash` (`device_hash`)) ENGINE = InnoDB; 

```

## Webserver
Any php compatible webserver
Tested with: php 7.3.33
