<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class MerchantDataBuilder
 */
class MerchantDataBuilder implements BuilderInterface
{
    const ACCESS_KEY = 'access_key';

    const PROFILE_ID = 'profile_id';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        return [
            self::ACCESS_KEY => $this->config->getValue(
                self::ACCESS_KEY,
                $paymentDO->getOrder()->getStoreId()
            ),
            self::PROFILE_ID => $this->config->getValue(
                self::PROFILE_ID,
                $paymentDO->getOrder()->getStoreId()
            )
        ];
    }
}
