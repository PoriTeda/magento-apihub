<?php
namespace Riki\NpAtobarai\Gateway\Http\Client;

use Zend_Http_Response;

/**
 * Class ResponseFactory
 */
class ResponseFactory
{
    /**
     * Create a new Zend_Http_Response object from a string
     *
     * @param string $response
     * @return Zend_Http_Response
     */
    public function create($response)
    {
        return Zend_Http_Response::fromString($response);
    }
}
