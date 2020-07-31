<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Framework\Config\ScopeInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class MerchantSecureDataBuilder
 */
class MerchantSecureDataBuilder implements BuilderInterface
{
    const MERCHANT_SECURE_DATA1 = 'merchant_secure_data1';

    const MERCHANT_SECURE_DATA2 = 'merchant_secure_data2';

    const MERCHANT_SECURE_DATA3 = 'merchant_secure_data3';

    /**
     * @var ScopeInterface
     */
    private $scope;

    /**
     * @param ScopeInterface $scope
     */
    public function __construct(ScopeInterface $scope)
    {
        $this->scope = $scope;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        return [
            static::MERCHANT_SECURE_DATA1 => $paymentDO->getOrder()->getId(),
            static::MERCHANT_SECURE_DATA2 => $paymentDO->getOrder()->getStoreId(),
            static::MERCHANT_SECURE_DATA3 => $this->scope->getCurrentScope()
        ];
    }
}
