<?php

namespace Riki\Subscription\Model\Emulator\Helper;

class Payment
    extends \Magento\Payment\Helper\Data
{
    const XML_PATH_EMULATOR_PAYMENT_METHODS = 'emulator_payment';


    /**
     * @param string $code
     * @return string
     */
    protected function getMethodModelConfigName($code)
    {
        return sprintf('%s/%s/model', self::XML_PATH_EMULATOR_PAYMENT_METHODS, $code);
    }
}