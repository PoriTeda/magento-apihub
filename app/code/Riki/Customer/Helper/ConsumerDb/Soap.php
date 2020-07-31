<?php

namespace Riki\Customer\Helper\ConsumerDb;

class Soap extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getCommonRequestParams()
    {
        return [
            'soap_version' => SOAP_1_2,
            'encoding' => 'UTF-8',
            'connection_timeout' => 30,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_DEFLATE
        ];
    }
}
