<?php

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Connect\MySQLiConnector;

Injector::inst()->load([
    'SilverStripe\ORM\Connect\Database' => [
        'properties' => [
            'connector' => '%$SilverStripe\ORM\Connect\MySQLiConnector'
        ]
    ]
]);
