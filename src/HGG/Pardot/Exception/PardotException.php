<?php

namespace HGG\Pardot\Exception;

/**
 * PardotException
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class PardotException extends \RuntimeException
{
    protected $details = array();

    public function __construct($message = '', $code = 0, $previous = null, $url = '', $parameters = array())
    {
        parent::__construct($message, $code, $previous);
        $this->details['url'] = $url;
        $this->details['parameters'] = $parameters;
    }

    public function getUrl()
    {
        return $this->details['url'];
    }

    public function setUrl($url)
    {
        $this->details['url'] = $url;
    }

    public function getParameters()
    {
        return $this->details['parameters'];
    }

    public function setParameters($parameters)
    {
        $this->details['parameters'] = $parameters;
    }

    public function getDetails()
    {
        return $this->details;
    }
}
