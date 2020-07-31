<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class ShippingAddressDataBuilder
 */
class ShippingAddressDataBuilder implements BuilderInterface
{
    /**
     * ShippingAddress block name
     */
    const SHIPPING_ADDRESS = 'ShippingAddress';

    /**
     * The method used to ship the customer’s order
     *
     * One of: Unknown, LowCost, DesignatedByCustomer, International, Military,
     * NextDay, StorePickup, TwoDayService, ThreeDayService, Other
     */
    const SHIPPING_METHOD = 'ShippingMethod';

    /**
     * The first name of the person the order is shipped to
     */
    const FIRST_NAME = 'FirstName';

    /**
     * Last name of the person the order is shipped to
     */
    const LAST_NAME = 'LastName';

    /**
     * The street address the order is shipped to
     */
    const STREET_1 = 'Street1';

    /**
     * The street address of the shipping location
     */
    const STREET_2 = 'Street2';

    /**
     * The city / suburb of the shipping location
     */
    const CITY = 'City';

    /**
     * The state / county of the shipping location
     */
    const STATE = 'State';

    /**
     * The post / zip code of the shipping location
     */
    const POSTAL_CODE = 'PostalCode';

    /**
     * The country of the shipping location. This should be the two letter ISO 3166-1 alpha-2 code.
     * This field must be lower case.
     * e.g. Australia = au
     *
     * @link https://www.iso.org/obp/ui/#search/code/
     */
    const COUNTRY = 'Country';

    /**
     * The email address of the person the order is shipped to, which must be correctly formatted if present.
     */
    const EMAIL = 'Email';

    /**
     * The phone number of the person the order is shipped to
     */
    const PHONE = 'Phone';

    /**
     * The fax number of the shipping location
     */
    const FAX = 'Fax';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $shippingAddress = $order->getShippingAddress();

        if (!$shippingAddress) {
            return [];
        }

        return [
            self::SHIPPING_ADDRESS => [
                self::FIRST_NAME => $shippingAddress->getFirstname(),
                self::LAST_NAME => $shippingAddress->getLastname(),
                self::STREET_1 => $shippingAddress->getStreetLine1(),
                self::STREET_2 => $shippingAddress->getStreetLine2(),
                self::CITY => $shippingAddress->getCity(),
                self::STATE => $shippingAddress->getRegionCode(),
                self::COUNTRY => strtolower($shippingAddress->getCountryId()),
                self::POSTAL_CODE => $shippingAddress->getPostcode(),
                self::PHONE => $shippingAddress->getTelephone(),
            ]
        ];
    }
}
