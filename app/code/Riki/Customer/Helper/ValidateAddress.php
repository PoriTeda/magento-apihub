<?php

namespace Riki\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\InputException;

class ValidateAddress extends AbstractHelper
{
    /**
     * Validate data address
     *
     * @param $address
     * @return InputException
     * @throws \Zend_Validate_Exception
     */
    public function validate($address)
    {
        $exception = new InputException();
        if ($address->getPostcode()) {
            if (!preg_match_all('/\d{3}(-)?\d{4}/m', $address->getPostcode())) {
                $exception->addError(__('Your Postcode must be in the format 000-0000'));
            }
        }

        if ($address->getRegionId() <= 0) {
            $region = $address->getRegion();
            if ($region && $region->getRegionId() > 0) {
                $address->setRegionId($region->getRegionId());
            }
        }

        if ($address->getRegionId() <= 0) {
            $exception->addError(__('Prefectures is a required field.'));
        }

        return $exception;
    }
}
