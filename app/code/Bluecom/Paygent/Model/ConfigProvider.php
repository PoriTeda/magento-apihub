<?php

namespace Bluecom\Paygent\Model;

use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\ConfigProviderInterface;

final class ConfigProvider implements ConfigProviderInterface
{
    const PAYGENT_CODE = 'paygent';

    const TRANSACTION_DATA_URL = 'paygent/paygent/paygent';
    /**
     * @var UrlInterface
     */
    private $_urlBuilder;

    /**
     * ConfigProvider constructor.
     * 
     * @param UrlInterface $urlBuilder UrlInterface
     */
    public function __construct(UrlInterface $urlBuilder)
    {
        $this->_urlBuilder = $urlBuilder;
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
                'paygent' => [
                    'transactionDataUrl' => $this->_urlBuilder->getUrl(self::TRANSACTION_DATA_URL, ['_secure' => true])
                ]
            ]
        ];
    }

}

