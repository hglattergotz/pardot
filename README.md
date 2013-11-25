A PHP implementation of a Pardot API connector

[![Build Status](https://travis-ci.org/hglattergotz/pardot.png)](https://travis-ci.org/hglattergotz/pardot)

**Goals:**

 * Provide a single method to access any of the Pardot endpoints
 * Parse the response and return only the data
 * Take care of error handling in the form of raising exceptions

## Installation

 * Via [Composer](http://getcomposer.org), package [hgg/pardot](https://packagist.org/packages/hgg/pardot)

## Usage

### Create a prospect

```php
<?php

use HGG\Pardot\Connector;

// email, user-key and password are required
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
