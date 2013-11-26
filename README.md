# Pardot

[![Build Status](https://travis-ci.org/hglattergotz/pardot.png)](https://travis-ci.org/hglattergotz/pardot)

**Pardot** is an API connector for the [Pardot API](http://developer.pardot.com/kb/api-version-3/introduction-table-of-contents) implemented in PHP. It facilitates
access to all the API endpoints that Pardot exposes. This can be used to build a custom CRM connector.

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

## Conventions

The Pardot API should be accessed via POST for all operations (recommended by Pardot).
For the most part the API does not use the standard HTTP response codes to convey the
outcome of the request, rather it always returns 2** response codes and sends back its
own set of status codes that need to be handled.
This connector captures the status codes and messages and throws exceptions that
contain this information. If it should be necessary to handle the individual cases
this is possible by catching the exception and getting the code from it.

All exceptions emitted by the library implement the ExceptionInterface. Any
exceptions raised in the HTTP layer are wrapped into a RequestException.

See Error Handling for more details.

## Usage

### Instantiating the Connector

The first argument to the constructor is an associative array containing the
initialization parameters for the connector.

**Required**

 * ```email``` - The email address of the user account
 * ```user-key``` - The user key of the user account
 * ```password``` - The account password

**Optional**

 * ```format``` - The content format. Pardot supports json and xml. Default ```json```
 * ```output``` - The level of detail that is returned. Possible values are ```full```, ```simple```, ```mobile```. Default ```full```
 * ```api-key``` - If the API key is being cached it can be injected into the constructor. Default ```null```

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

## Error Handling

All library exceptions implement the common ExceptionInterface interface.

#### Exception Hierarchy

All library exceptions extend the common SPL exceptions.

```
\Exception
  |
  |- ExceptionCollection
  |
  |- \LogicException
  |    |- \InvalidArgumentExcpetion
  |         |- InvalidArgumentExcpetion
  |
  |- \RuntimeException
       |- RuntimeException
           |- AuthenticationErrorException
           |- RequestException
```

The following exceptions are thrown:

#### AuthenticationErrorException

When authentication against the Pardot API fails.

#### ExceptionCollection

When the Pardot API returns error code 10000 that can contain multiple errors.

#### InvalidArgumentException

Type errors such as passing an invalid Connector construction parameters.

#### RequestException

Exceptions emitted by the HTTP layer (GuzzlePHP' HTTPException) are wraped in a
RequestExcpetion.

#### RuntimeException

All non HTTPExceptions from Guzzle and non authentication errors returned by the
Pardot API.

### Examples

#### Catch any HGG\Pardot exception

If not specific error handling is required or needed just use this as a catch-all.

```php
<?php

use HGG\Pardot\Exception\ExceptionInterface;

try {
    // Pardot library code
} catch (ExceptionInterface $e) {
    // Handle it here
}
```


