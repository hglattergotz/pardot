# Pardot

[![Build Status]](http://travis-ci.org/hglattergotz/pardot):w

**Pardot** is an API connector for the [Pardot API](http://developer.pardot.com/kb/api-version-3/introduction-table-of-contents) implemented in PHP. It facilitates
access to all the API endpoints that Pardot exposes.

* Install via [Composer](http://getcomposer.org) package [hgg/pardot](https://packagist.org/packages/hgg/pardot)

## Goals

 * Provide a single method for executing any command on any of the Pardot endpoints
 * Parse the response and return only the data (decoded JSON or SimpleXmlElement)
 * Take care of error handling

## Dependencies

 * [Guzzle](http://docs.guzzlephp.org/en/latest/#) - PHP HTTP Client
 * [Collections](https://github.com/IcecaveStudios/collections) - A really nice implementation of common data structures
 * [parameter-validator](https://github.com/hglattergotz/parameter-validator) - A parameter validator library

Why so many dependencies for such a small lib? Well, why reinvent the wheel?

## Usage

### Instantiating the Connector

The first argument to the constructor is an associative array containing the
initialization parameters for the connector.

**Required**

 * ```email``` - The email address of the user account
 * ```user-key``` - The user key of the user account
 * ```password``` - The account password

**Optional**

 * ```format``` - The content format. Pardot supports json and xml (json is default)
 * ```output``` - The level of detail that is returned. ```full```, ```simple```, ```mobile```
 * ```api-key``` - If the API key is being cached it can be injected into the constructor

For testing purposes the HTTP client can also be injected. If not, it is instantiated.

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

$connector = new Connector($connectorParameters, null);
```

### Create a prospect

The minimally required set of parameters/fields for creating a prospect is the
email address of the prospect.
This will create a prospect and return the full prospect record as a PHP array
because the connector was instantiated with *format = json* and *output = full*.
If the format is set to *xml* the method will return a SimpleXmlElement instance.

```php
<?php

$response = $connector->post('prospect', 'create', array('email' => 'some@example.com'));
```

```prospect``` is the object that will be accessed and ```create``` is the operation
performed on that object. The third parameter is an associative array of fields to be
set.

### Read (get) a prospect by the email address

```php
<?php

$response = $connector->post('prospect', 'read', array('email' => 'some@example.com'));
```
