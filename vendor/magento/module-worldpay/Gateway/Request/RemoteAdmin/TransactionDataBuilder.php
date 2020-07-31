<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Gateway\Request\RemoteAdmin;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class TransactionDataBuilder implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        return [
            'authPW' => $this->config->getValue(
                'auth_password',
                $paymentDO->getOrder()->getStoreId()
            ),
            'instId' => $this->config->getValue(
                'admin_installation_id',
                $paymentDO->getOrder()->getStoreId()
            ),
            'testMode' => (bool)$this->config->getValue(
                'test_mode',
                $paymentDO->getOrder()->getStoreId()
            )
                ? 100
                : 0
        ];
    }
}
