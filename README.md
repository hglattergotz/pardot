Pardot API connector

*Goals:*

 * Provide single method for executing any command on any of the endpoints
 * Parse the response and return only the data
 * Take care of error handling

[![Build Status](https://travis-ci.org/hglattergotz/pardot.png)](https://travis-ci.org/hglattergotz/pardot)

## Installation

Using Composer:

```json
{
    "require": {
        "hgg/pardot": "dev-master"
    }
}
```

## Usage

### Create a prospect

```php
<?php

use HGG\Pardot\Connector;
use HGG\Pardot\Exception\PardotException;

$connectorParameters = array(
    'email'    => 'The email address of the user account',
    'user-key' => 'The user key of the user account (in My Settings)',
    'password' => 'The account password',
    'format'   => 'json',
    'output'   => 'full'
);

$connector = new Connector($connectorParameters);
$response = $connector->post('prospect', 'create', array('email' => 'some@example.com'));
```

### Read (get) a prospect by the email address

```php
<?php

// See above for setup

$connector = new Connector($connectorParameters);
$response = $connector->post('prospect', 'read', array('email' => 'some@example.com'));
```
