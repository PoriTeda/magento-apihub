<?php

namespace Riki\SapIntegration\Webapi;

class TransferFactory
{
    /**
     * Factory method for \Riki\SapIntegration\Webapi\Transfer
     *
     * @param string $wsdl
     * @param array $options
     * @return \Riki\SapIntegration\Webapi\Transfer
     */
    public function create($wsdl, array $options = [])
    {
        return new \Riki\SapIntegration\Webapi\Transfer($wsdl, $options);
    }
}