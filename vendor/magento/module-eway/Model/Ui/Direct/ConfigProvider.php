<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Model\Ui\Direct;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const EWAY_CODE = 'eway';

    const ENDPOINT_SANDBOX = 'Sandbox';

    const ENDPOINT_PRODUCTION = 'Production';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor
     *
     * @param ConfigInterface $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(ConfigInterface $config, UrlInterface $urlBuilder)
    {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::EWAY_CODE => [
                    'orderCancelUrl' => $this->urlBuilder->getUrl('eway/order/cancel', ['_secure' => true]),
                    'paymentGetAccessCodeUrl' => $this->urlBuilder->getUrl(
                        'eway/payment/getAccessCode',
                        ['_secure' => true]
                    ),
                    'paymentUpdateUrl' => $this->urlBuilder->getUrl('eway/payment/complete', ['_secure' => true]),
                    'connectionType' => $this->config->getValue('connection_type'),
                    'cryptUrl' => $this->config->getValue('crypt_script'),
                    'encryptKey' => (bool)$this->config->getValue('sandbox_flag')
                        ? $this->config->getValue('sandbox_encryption_key')
                        : $this->config->getValue('live_encryption_key'),
                    'endpoint' => (bool)$this->config->getValue('sandbox_flag')
                        ? self::ENDPOINT_SANDBOX
                        : self::ENDPOINT_PRODUCTION
                ]
            ]
        ];
    }
}
