<?php

namespace HGG\Pardot;

use HGG\Pardot\ResponseHandler\JsonResponseHandler;
use HGG\Pardot\ResponseHandler\XmlResponseHandler;
use HGG\Pardot\Exception\ExceptionInterface;
use HGG\Pardot\Exception\InvalidArgumentException;
use HGG\Pardot\Exception\RuntimeException;
use HGG\Pardot\Exception\AuthenticationErrorException;
use HGG\Pardot\Exception\RequestException;

use Guzzle\Http\Client;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\GuzzleException;
use HGG\ParameterValidator\Validator\ArrayValidator;
use Icecave\Collections\Map;

/**
 * A convenience class that takes care of the various task that are necessary
 * for sending and receiving data to and from the Pardot API:
 *
 *  * Authentication
 *  * URL generation based on desired operation and object
 *  * Encoding the data to send
 *  * Decoding the response data
 *  * Error handling
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class Connector
{
    /**
     * The email address of the user account being used for API access
     *
     * @var string
     * @access protected
     */
    protected $email;

    /**
     * The user key that is unique to the user account bing used for API access
     *
     * This can be found in the Pardot user interface in 'My Settings'.
     *
     * @var string
     * @access protected
     */
    protected $userKey;

    /**
     * password
     *
     * @var string
     * @access protected
     */
    protected $password;

    /**
     * The API key that is obtained by the authentication step and must be sent
     * on all subsequent requests
     *
     * See http://developer.pardot.com/kb/api-version-3/authentication
     * for more details
     *
     * @var string
     * @access protected
     */
    protected $apiKey  = null;

    /**
     * The API base URL
     *
     * @var string
     * @access protected
     */
    protected $baseUrl = 'https://pi.pardot.com';

    /**
     * The Pardot API version - currently 3
     *
     * @var string
     * @access protected
     */
    protected $version = '3';

    /**
     * The output type, which can be full, simple or mobile
     *
     * See http://developer.pardot.com/kb/api-version-3/using-the-pardot-api#changing-xml-response-format
     * for details
     *
     * @var string
     * @access protected
     */
    protected $output  = 'full';

    /**
     * The response format - json/xml
     *
     * @var string
     * @access protected
     */
    protected $format  = 'json';

    /**
     * totalResults
     *
     * @var integer
     * @access protected
     */
    protected $totalResults = 0;

    /**
     * httpClientOptions
     *
     * @var mixed
     * @access protected
     */
    protected $httpClientOptions;

    /**
     * rawResponse
     *
     * @var mixed
     * @access protected
     */
    protected $rawResponse;

    /**
     * __construct
     *
     * @param array               $parameters
     * @param \Guzzle\Http|Client $httpClient
     * @param array               $httpClientOptions
     *
     * @access public
     *
     * @return void
     */
    public function __construct(
        array $parameters,
        \Guzzle\Http\Client $httpClient = null,
        $httpClientOptions = array('timeout' => 10))
    {
        try {
            $required = array('email', 'user-key', 'password');
            $optional = array('base-url', 'version', 'output', 'format', 'api-key');
            ArrayValidator::contains(array_keys($parameters), $required, $optional, false);
            $map = new Map($parameters);

            $this->httpClientOptions = $httpClientOptions;

            $this->email    = $map->get('email');
            $this->userKey  = $map->get('user-key');
            $this->password = $map->get('password');
            $this->baseUrl  = $map->getWithDefault('base-url', $this->baseUrl);
            $this->version  = $map->getWithDefault('version', $this->version);
            $this->output   = $map->getWithDefault('output', $this->output);
            $this->format   = $map->getWithDefault('format', $this->format);
            $this->apiKey   = $map->getWithDefault('api-key', null);

            $this->client = (null === $httpClient) ? $this->getClient() : $httpClient;
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Make a call to the login endpoint and obtain an API key
     *
     * This step is not absolutely necessary, because the post method will check
     * for the presence of a key and will call this method if there is none.
     *
     * @access public
     *
     * @return void
     */
    public function authenticate()
    {
        $object = 'login';
        $url = sprintf('/api/%s/version/%s', $object, $this->version);
        $parameters = array(
            'email'    => $this->email,
            'password' => $this->password,
            'user_key' => $this->userKey,
            'format'   => $this->format
        );

        $this->apiKey = $this->doPost($object, $url, $parameters);

        return $this->apiKey;
    }

    /**
     * Gets the currently active api_key
     *
     * An API key obtained from the API upon authentication is valid for 60
     * minutes. If the Connector object is used for multiple requests during its
     * lifetime, the originally obtained API key is reused to avoid making a
     * login request before every post request.
     * If a Connector instance is created many times during the time span in
     * which the API key is valid (because on a cron job or if initiated by user
     * actions) it can also make sense to cache the key either on disk or in
     * some other syste like memcache.
     * In this case the API key would be fetched with this method before the
     * object is destroyed and stored in a persistent store.
     *
     * The next time the Connector is instantiated the api-key parameter can be
     * passed and the call to authenticate can be skipped.
     *
     * @access public
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Makes necessary preparations for a POST request to the Pardot API,
     * handles authentication retries
     *
     * The method constructs a valid url based on the object and the operator.
     * If the API key is null, it also makes a call to the authenticate method.
     * If an API key is present but happens to be stale, this is detected as
     * well and a call to authenticate is made to get a new key.
     *
     * @param string $object     The name of the Pardot object of interest
     * @param string $operation  The operation to be performed on the object
     * @param array  $parameters The parameters to send
     *
     * @access public
     *
     * @return mixed            In case of format=JSON it returns PHP arrays, if
     *                          format=XML it will either return a single
     *                          SimpleXMLElement or an array of SimpleXmlElements
     *
     * @throws                  HGG\Pardot\RequestException
     *                          HGG\Pardot\RuntimeException
     */
    public function post($object, $operation, $parameters)
    {
        if (null === $this->apiKey) {
            $this->authenticate();
        }

        $url = sprintf('/api/%s/version/%s/do/%s', $object, $this->version, $operation);
        $parameters = array_merge(
            array(
                'api_key'  => $this->apiKey,
                'user_key' => $this->userKey,
                'format'   => $this->format,
                'output'   => $this->output
            ),
            $parameters
        );

        try {
            return $this->doPost($object, $url, $parameters);
        } catch (AuthenticationErrorException $e) {
            $this->authenticate();
            $parameters['api_key'] = $this->apiKey;

            return $this->doPost($object, $url, $parameters);
        }
    }

    /**
     * Returns the response document that the api returns before any processing
     * takes place. It will either be a PHP array or a SimpleXmlElement
     * depending on whether the requested format is json or xml.
     * This can be used for logging purposes.
     *
     * @access public
     * @return mixed
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * Returns the total record count for the last query
     *
     * If it is higher than 200, subsequent queries will be necessary to get
     * the entire result set from the server.
     *
     * @access public
     * @return integer
     */
    public function getResultCount()
    {
        return $this->totalResults;
    }

    /**
     * Construct the Guzzle Http Client with the exponential retry plugin
     *
     * @access protected
     * @return \Guzzle|Http\Client
     */
    protected function getClient()
    {
        $retries   = 5;
        $httpCodes = null;
        $curlCodes = null;

        $plugin = BackoffPlugin::getExponentialBackoff($retries, $httpCodes, $curlCodes);
        $client = new Client($this->baseUrl);
        $client->addSubscriber($plugin);

        return $client;
    }

    /**
     * Makes the actual HTTP POST call to the API, parses the response and
     * returns the data if there is any. Throws exceptions in case of error.
     *
     * @param string $object     The name of the object to perform an operation
     *                           on
     * @param string $url        The URL that will be accessed
     * @param array  $parameters The parameters to send
     *
     * @access protected
     *
     * @return mixed            In case of format=JSON it returns PHP arrays, if
     *                          format=XML it will either return a single
     *                          SimpleXMLElement or an array of SimpleXmlElements
     *
     * @throws                  HGG\Pardot\RequestException
     *                          HGG\Pardot\RuntimeException
     */
    protected function doPost($object, $url, $parameters)
    {
        $httpResponse = null;
        $headers = null;
        $postBody = null;
        $options = $this->httpClientOptions;

        try {
            $httpResponse = $this->client->post($url, $headers, $postBody, $options)
                ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->setBody(http_build_query($parameters))
                ->send();
        } catch (HttpException $e) {
            $msg = sprintf('%s. Http status code [%s]', $e->getMessage(), $e->getResponse()->getStatusCode());

            throw new RequestException($msg, 0, $e, $url, $parameters);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e, $url, $parameters);
        }

        if (204 == $httpResponse->getStatusCode()) {
            return array();
        }

        try {
            $handler = $this->getHandler($httpResponse, $object);
            $result = $handler->parse()->getResult();
            $this->totalResults = $handler->getResultCount();

            return $result;
        } catch (ExceptionInterface $e) {
            $e->setUrl($url);
            $e->setParameters($parameters);

            throw $e;
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e, $url, $parameters);
        }
    }

    /**
     * Instantiate the appropriate Response document handler based on the
     * format
     *
     * @param Guzzle\Http|Message|Response $response The Guzzle Response object
     * @param string                       $object   The name of the pardot obj
     *
     * @access protected
     *
     * @return HGG\Pardot\AbstractResponseHanler
     */
    protected function getHandler($response, $object)
    {
        $handler = null;

        switch ($this->format) {
        case 'json':
            $this->rawResponse = $response->json();
            $handler = new JsonResponseHandler($this->rawResponse, $object);
            break;
        case 'xml':
            $this->rawResponse = $response->xml();
            $handler = new XmlResponseHandler($this->rawResponse, $object);
            break;
        }

        return $handler;
    }
}
