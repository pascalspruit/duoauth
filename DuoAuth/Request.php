<?php

namespace DuoAuth;

use Guzzle\Http\Client;

class Request
{
    /**
     * Request path
     * @var string
     */
    private $path = null;

    /**
     * Curent client object
     * @var \Guzzle\Http\Client
     */
    private $client = null;

    /**
     * HTTP method for the request
     * @var string
     */
    private $method = 'GET';

    /**
     * Parameters for the request
     * @var array
     */
    private $params = array();

    /**
     * API hostname for request
     * @var string
     */
    private $hostname = null;

    /**
     * Integration key (used in auth)
     * @var string
     */
    private $intKey = null;

    /**
     * Secret key (used in hash generation)
     * @var string
     */
    private $secretKey = null;

    /**
     * List of errors on current request
     * @var array
     */
    private $errors = array();

    /**
     * Initialize the Request object
     */
    public function __construct()
    {
        // make the Guzzle client
        $client = new Client();
        $this->setClient($client);
    }

    /**
     * Undefined methods should be passed directly to the Guzzle client (if exist)
     *
     * @param string $func Function name
     * @param array $args Function arguments
     * @return mixed|boolean Function return or false on not-exist
     */
    public function __call($func, $args)
    {
        if (method_exists($this->client, $func)) {
            return call_user_func_array(array($this->client, $func), $args);
        }
        return false;
    }

    /**
     * Build the hash header based off values in the current object
     *
     * @return strin SHA1 hash for request contents
     */
    private function buildHashHeader()
    {
        $params = $this->getParams();
        ksort($params);
        $paramStr = http_build_query($params);

        $hash = array();
        $hash[] = strtoupper($this->getMethod());
        $hash[] = $this->getHostname();
        $hash[] = $this->getPath();
        $hash[] = $paramStr;

        $hash = hash_hmac('sha1', implode("\n", $hash), $this->getSecretKey());

        return $hash;
    }

    /**
     * Send the request to the API
     *
     * @return string|boolean Parsed json if successful, false if not
     */
    public function send()
    {
        $path = 'https://'.$this->getHostname().$this->getPath();
        $method = strtolower($this->getMethod());
        $client = $this->getClient();

        $hash = $this->buildHashHeader();
        $params = $this->getParams();
        ksort($params);

        $request = $client->$method($path, null, $params)
            ->setAuth($this->getIntKey(), $hash);

        try {
            $response = $request->send();
            return $response->json();

        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

    }

    /**
     * Get the current error list
     *
     * @return array Error list
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set the object's integration key
     *
     * @param string $key Integration key
     * @return \DuoAuth\Request instance
     */
    public function setIntKey($key)
    {
        $this->intKey = $key;
        return $this;
    }

    /**
     * Get the object's integration key
     *
     * @return string Integration key
     */
    public function getIntKey()
    {
        return $this->intKey;
    }

    /**
     * Set the secret key for request
     *
     * @param string $key Secret key
     * @return \DuoAuth\Request instance
     */
    public function setSecretKey($key)
    {
        $this->secretKey = $key;
        return $this;
    }
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * Set the hostname for the current request
     *
     * @param string $hostname Hostname to set
     * @return \DuoAuth\Request instance
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * Get the hostname for the current request
     * @return string Current hostname
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Set the HTTP method to use for the request
     *     NOTE: Class default is GET
     *
     * @param string $method HTTP method
     * @return \DuoAuth\Request instance
     */
    public function setMethod($method)
    {
        $this->method = strtolower($method);
        return $this;
    }

    /**
     * Get the HTTP method for request
     *
     * @return string HTTP method
     */
    public function getMethod()
    {
        return strtoupper($this->method);
    }

    /**
     * Set the client for the current request
     *
     * @param \Guzzle\Http\Client $client Guzzle client
     * @return \DuoAuth\Request instance
     */
    public function setClient(\Guzzle\Http\Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get the current client object
     *
     * @return \Guzzle\Http\Client instance
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * [setPath description]
     *
     * @param string $path Path to set
     * @param string $format Format for reqponse (default: json)
     * @return \DuoAuth\Request instance
     */
    public function setPath($path, $format = null)
    {
        if ($format == null && strpos($path, '.json') == false) {
            $path .= '.json';
        } elseif ($format !== null) {
            $path .= '.'.$format;
        }
        $this->path = $path;
        return $this;
    }

    /**
     * Get the request's current path
     *
     * @return string Current path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * [setParams description]
     *
     * @param [type] $params [description]
     * @return \DuoAuth\Request instance
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }
    public function getParams()
    {
        return $this->params;
    }

}