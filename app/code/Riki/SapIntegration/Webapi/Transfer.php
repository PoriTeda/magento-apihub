<?php

namespace Riki\SapIntegration\Webapi;

class Transfer extends \SoapClient
{
    /**
     * @var string
     */
    protected $xmlRequest;

    /**
     * The purpose of this function is getting xml request from SOAP Var
     *
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int $oneWay
     * @return string
     */
    public function __doRequest($request, $location, $action, $version, $oneWay = 0)
    {
        $this->xmlRequest = $request;
        return "";
    }

    /**
     * XML Request
     *
     * @return string
     */
    public function getXmlRequest()
    {
        return $this->xmlRequest;
    }
}