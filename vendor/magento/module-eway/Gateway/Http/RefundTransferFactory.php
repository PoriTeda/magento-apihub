<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Http;

use Magento\Eway\Gateway\Helper\TransactionReader;
use Magento\Eway\Gateway\Http\Client\Curl;
use Magento\Eway\Gateway\Request\TransactionIdDataBuilder;

/**
 * Class RefundTransferFactory
 */
class RefundTransferFactory extends AbstractTransferFactory
{
    /**
     * @inheritdoc
     */
    public function create(array $request)
    {
        return $this->transferBuilder
            ->setMethod(Curl::POST)
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setBody(json_encode($request, JSON_UNESCAPED_SLASHES))
            ->setAuthUsername($this->getApiKey())
            ->setAuthPassword($this->getApiPassword())
            ->setUri($this->getUrl($request))
            ->build();
    }

    /**
     * @param array $request
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getUrl($request)
    {
        return $this->action->getUrl('/' . TransactionReader::readTransactionId($request) . '/Refund');
    }
}
