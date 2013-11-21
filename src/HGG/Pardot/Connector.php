<?php

namespace HGG\Pardot;

use HGG\Pardot\ResponseHandler\JsonResponseHandler;
use HGG\Pardot\ResponseHandler\XmlResponseHandler;
use HGG\Pardot\Exception\PardotException;
use HGG\Pardot\Exception\PardotAuthenticationErrorException;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use HGG\ParameterValidator\Validator\ArrayValidator as v;
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
     * passpord
     *
     * @var string
     * @access protected
     */
    protected $passpord;

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
     * __construct
     *
     * @param array $parameters
     * @param bool $httpClient
     * @access public
     *
     * @return void
     */
    public function __construct(array $parameters, $httpClient = null)
    {
        try {
            $required = array('email', 'user-key', 'password');
            $optional = array('base-url', 'version', 'output', 'format', 'api-key');
            v::contains(array_keys($parameters), $required, $optional, false);
            $map = new Map($parameters);

            $this->email    = $map->get('email');
            $this->userKey  = $map->get('user-key');
            $this->password = $map->get('password');
            $this->baseUrl  = $map->getWithDefault('base-url', $this->baseUrl);
            $this->version  = $map->getWithDefault('version', $this->version);
            $this->output   = $map->getWithDefault('output', $this->output);
            $this->format   = $map->getWithDefault('format', $this->format);
            $this->apiKey   = $map->getWithDefault('api-key', null);

            $this->client = (null === $httpClient) ? new Client($this->baseUrl) : $httpClient;
        } catch (Exception $e) {
            throw new PardotException($e->getMessage(), 0, $e);
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
     * live time, the originally obtained API key is reused to avoid making a
     * login request before every post request.
     * If a Connector instance is created many times during the time span in
     * which the API key is valid (because on a cron job or if initiated by user
     * actions) it can also make sense to cache the key either on disk or in
     * some other syste like memcache.
     * In this case the API key would be fetched with this method before the
     * object is destroyed and stored.
     *
     * The next time the Connector is instantiated the api-key parameter can be
     * passed and the call to authenticate can be skipped.
     *
     * @access public
     *
     * @return void
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
     * @param string $object    The object of interest
     * @param string $operator  The operation to be performed
     * @param array $parameters The parameters to send
     * @access public
     *
     * @return mixed            If format is XML then an array of
     *                          SimpleXmlElement objects, else an associative
     *                          array (decoded JSON)
     *
     * @throws Exception\PardotException
     */
    public function post($object, $operator, $parameters)
    {
        if (null === $this->apiKey) {
            $this->authenticate();
        }

        $url = sprintf('/api/%s/version/%s/do/%s', $object, $this->version, $operator);
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
        } catch (PardotAuthenticationErrorException $e) {
            $this->authenticate();

            return $this->doPost($object, $url, $parameters);
        }
    }

    /**
     * Makes the actual HTTP POST call to the API, parses the response and
     * returns it
     *
     * @param string $object
     * @param string $url
     * @param array $parameters
     * @access protected
     *
     * @return mixed
     */
    protected function doPost($object, $url, $parameters)
    {
        $httpResponse = null;

        try {
            $httpResponse = $this->client->post($url)
                ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->setBody(http_build_query($parameters))
                ->send();
        } catch (BadResponseException $e) {
            $msg = sprintf('%s. Http status code [%s]', $e->getMessage(), $e->getResponse()->getStatusCode());

            throw new PardotException($msg, 0, $e, $url, $parameters);
        } catch (Exception $e) {
            throw new PardotException($e->getMessage(), 0, $e, $url, $parameters);
        }

        if (204 == $httpResponse->getStatusCode()) {
            return array();
        }

        try {
            return $this->getHandler($httpResponse, $object)->getResult();
        } catch (PardotException $e) {
            $e->setUrl($url);
            $e->setParameters($parameters);

            throw $e;
        } catch (\Exception $e) {
            throw new PardotException($e->getMessage(), 0, $e, $url, $parameters);
        }
    }

    /**
     * Instantiate the appropriate Response document handler
     *
     * @param Guzzle\Http|Message|Response $response
     * @param string $object
     * @access protected
     *
     * @return void
     */
    protected function getHandler($response, $object)
    {
        $handler = null;

        switch ($this->format) {
        case 'json':
            $handler = new JsonResponseHandler($response->json(), $object);
            break;
        case 'xml':
            $handler = new XmlResponseHandler($response->xml(), $object);
            break;
        }

        return $handler;
    }
}
