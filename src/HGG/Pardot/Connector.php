<?php

namespace HGG\Pardot;

use HGG\Pardot\Exception\PardotException;
use HGG\Pardot\Exception\PardotAuthenticationErrorException;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use HGG\ParameterValidator\Validator\ArrayValidator as v;
use Icecave\Collections\Map;

/**
 * Connector
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class Connector
{
    protected $email;
    protected $userKey;
    protected $passpord;
    protected $apiKey  = null;
    protected $baseUrl = 'https://pi.pardot.com';
    protected $version = '3';
    protected $output  = 'full';
    protected $format  = 'json';

    /**
     * __construct
     *
     * @param array $parameters
     * @access public
     * @return void
     */
    public function __construct(array $parameters, $httpClient = null)
    {
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
    }

    /**
     * authenticate
     *
     * @access public
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
     * post
     *
     * @param mixed $object
     * @param mixed $operator
     * @param mixed $parameters
     * @access public
     * @return void
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
     * doPost
     *
     * @param mixed $object
     * @param mixed $url
     * @param mixed $parameters
     * @access protected
     * @return array
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
            sprintf('%s. Http status code [%s]', $e->getMessage(), $e->getResponse()->getStatusCode());

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
     * getHandler
     *
     * @param mixed $response
     * @param mixed $object
     * @access protected
     * @return void
     */
    protected function getHandler($response, $object)
    {
        $handler = null;
        $type = $this->getContentType($response);

        switch ($type) {
        case 'json':
            $handler = new JsonResponseHandler($response->json(), $object);
            break;
        case 'xml':
            $handler = new XmlResponseHandler($response->xml(), $object);
            break;
        }

        return $handler;
    }

    /**
     * getContentType
     *
     * @param mixed $response
     * @access protected
     * @return void
     */
    protected function getContentType($response)
    {
        if ($response->isContentType('xml')) {
            return 'xml';
        } elseif ($response->isContentType('json')) {
            return 'json';
        } else {
            $msg = sprintf('Invalid content type %s.', $response->getContentType());
            throw new PardotException($msg, 0, null);
        }
    }
}
