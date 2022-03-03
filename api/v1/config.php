<?php
// Copyright (c) 2022 Bruno Lange <brvn0>
// VERSION: 1.0
// Tested with
// - php 7.3.33
//
// Config for the Stats Api v1 for Project Materium

// the config files may vary from version to version so we put the file into the specific version folder

// this file is not the production config but just the local setup for my pc :)
// so don't even try the passwords on production lol

function getStatsConfig()
{
    return array(
        'DATABASE_HOST' => 'localhost',
        'DATABASE_NAME' => 'stats',
        'DATABASE_USERNAME' => 'mat_stats',
        'DATABASE_PASSWORD' => '*52A51B856D9E88A37F2BDB3C61A5C89273C1A9A1',
        'LIMIT_GETTOPCOUNTRIES' => 5,
        'LIMIT_GETTOPDEVICES' => 5,
        'LIMIT_GETDEVICE_TOP_COUNTRY' => 5,
        'LIMIT_GETDEVICE_TOP_VERSION' => 5
    );
}
