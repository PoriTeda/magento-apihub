<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class AbstractAddressDataBuilder
 */
abstract class AbstractAddressDataBuilder implements BuilderInterface
{
    const TO_ADDRESS_CITY = 'to_address_city';

    const TO_ADDRESS_COUNTRY = 'to_address_country';

    const TO_ADDRESS_LINE1 = 'to_address_line1';

    const TO_ADDRESS_POSTAL_CODE = 'to_address_postal_code';

    const TO_ADDRESS_STATE = 'to_address_state';

    const TO_EMAIL = 'to_email';

    const TO_PHONE = 'to_phone';

    const TO_FORENAME = 'to_forename';

    const TO_SURNAME = 'to_surname';

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $address = $this->getAddress($paymentDO->getOrder());

        if (!$address) {
            return [];
        }

        return [
            $this->getFieldSuffix() . self::TO_ADDRESS_CITY => $address->getCity(),
            $this->getFieldSuffix() . self::TO_ADDRESS_COUNTRY => $address->getCountryId(),
            $this->getFieldSuffix() . self::TO_ADDRESS_LINE1 => $address->getStreetLine1(),
            $this->getFieldSuffix() . self::TO_ADDRESS_POSTAL_CODE => $address->getPostcode(),
            $this->getFieldSuffix() . self::TO_ADDRESS_STATE => $address->getRegionCode(),
            $this->getFieldSuffix() . self::TO_EMAIL => $address->getEmail(),
            $this->getFieldSuffix() . self::TO_PHONE => $address->getTelephone(),
            $this->getFieldSuffix() . self::TO_FORENAME => $address->getFirstname(),
            $this->getFieldSuffix() . self::TO_SURNAME => $address->getLastname()
        ];
    }

    /**
     * Returns address object from order
     *
     * @param OrderAdapterInterface $order
     * @return AddressAdapterInterface|null
     */
    abstract protected function getAddress(OrderAdapterInterface $order);

    /**
     * Returns fields suffix
     *
     * @return string
     */
    abstract protected function getFieldSuffix();
}
