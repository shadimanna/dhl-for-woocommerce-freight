<?php

namespace PR\DHL\REST_API\Freight;

use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use PR\DHL\REST_API\URL_Utils;
use RuntimeException;

/**
 * The authorization controller for Deutsche Post.
 *
 * The Deutsche Post API requires that requests send an "Authorization: Bearer 123456" header, where  "123456" is an
 * access code. That access code is obtained from the REST API itself by sending the client ID and client secret,
 * encoded in a base64 string. The REST API should respond with a token, which will contain the code, its expiry,
 * type, etc.
 *
 * So the process for authorization involves first obtaining the token, storing it locally and then using it to
 * authorize regular REST API requests. This class stores the token in a transient with an expiry time that matches
 * the expiry time of the token as indicated by the Deutsche Post REST API.
 *
 * @since [*next-version*]
 *
 * @see https://api-qa.deutschepost.com/dpi-apidoc/#/reference/authentication/access-token/get-access-token
 */
class Auth implements API_Auth_Interface
{
    const AUTH_TYPE = 'client-key';

    /**
     * Client Key
     *
     * @var string
     */
    protected $client_key;

    /**
     * Auth constructor.
     * @param $client_key
     */
    public function __construct( $client_key ) {
        $this->client_key = $client_key;
    }

    /**
     * Add required auth param to header
     *
     * @param Request $request
     * @return Request
     */
    public function authorize(Request $request)
    {
        $request->headers[ self::AUTH_TYPE ] = $this->client_key;

        return $request;
    }
}