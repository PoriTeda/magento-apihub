<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Http\SilentOrder;

use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @param ConfigInterface $config
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        ConfigInterface $config,
        TransferBuilder $transferBuilder
    ) {
        $this->config = $config;
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        return $this->transferBuilder
            ->setClientConfig(
                [
                    'maxredirects' => 5,
                    'timeout' => 30,
                    'verifypeer' => 1
                ]
            )
            ->setHeaders([])
            ->setBody($request)
            ->setMethod(ZendClient::POST)
            ->setUri(
                (bool)$this->config->getValue('sandbox_flag')
                ? $this->config->getValue('transaction_url_test_mode')
                : $this->config->getValue('transaction_url')
            )
            ->shouldEncode(true)
            ->build();
    }
}
