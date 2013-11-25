<?php

namespace HGG\Pardot\Exception;

use HGG\Pardot\Exception\ExceptionInterface;

/**
 * InvalidArgumentException
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    protected $url;
    protected $parameters;

    public function __construct($message = '', $code = 0, $previous = null, $url = '', $parameters = array())
    {
        parent::__construct($message, $code, $previous);

        $this->url = $url;
        $this->parameters = $parameters;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }
}

