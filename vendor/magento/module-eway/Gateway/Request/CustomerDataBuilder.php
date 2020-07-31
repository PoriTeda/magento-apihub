<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class CustomerDataBuilder
 */
class CustomerDataBuilder implements BuilderInterface
{
    /**
     * Customer block name
     */
    const CUSTOMER = 'Customer';

    /**
     * The merchant’s reference for this customer
     */
    const REFERENCE = 'Reference';

    /**
     * The customer’s title, empty string allowed.
     *
     * One of: Mr., Ms., Mrs., Miss, Dr., Sir., Prof.
     *
     * WARNING:
     * Required when creating a new Token customer
     */
    const TITLE = 'Title';

    /**
     * The customer’s first name
     *
     * WARNING:
     * Required when creating a new Token customer
     */
    const FIRST_NAME = 'FirstName';

    /**
     * The customer’s last name
     *
     * WARNING:
     * Required when creating a new Token customer
     */
    const LAST_NAME = 'LastName';

    /**
     * The customer’s company name
     */
    const COMPANY_NAME = 'CompanyName';

    /**
     * The customer’s job description / title
     */
    const JOB_DESCRIPTION = 'JobDescription';

    /**
     * The customer’s street address
     */
    const STREET_1 = 'Street1';

    /**
     * The customer’s street address
     */
    const STREET_2 = 'Street2';

    /**
     * The customer’s city / town / suburb
     */
    const CITY = 'City';

    /**
     * The customer’s state / county
     */
    const STATE = 'State';

    /**
     * The customer’s post / zip code
     */
    const POSTAL_CODE = 'PostalCode';

    /**
     * The customer’s country. This should be the two letter ISO 3166-1 alpha-2 code.
     * This field must be lower case.
     * e.g. Australia = au
     *
     * WARNING:
     * Required when creating a new Token customer.
     * When this field is present, along with the customer’s IP address,
     * any transaction will be processed using Beagle Fraud Alerts
     *
     * @link https://www.iso.org/obp/ui/#search/code/
     */
    const COUNTRY = 'Country';

    /**
     * The customer’s email address, which must be correctly formatted if present
     */
    const EMAIL = 'Email';

    /**
     * The customer’s phone number
     */
    const PHONE = 'Phone';

    /**
     * The customer’s fax number
     */
    const MOBILE = 'Mobile';

    /**
     * The customer’s fax number
     */
    const FAX = 'Fax';

    /**
     * The customer’s website, which must be correctly formatted if present
     */
    const URL = 'Url';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $billingAddress = $order->getBillingAddress();

        return [
            self::CUSTOMER => [
                self::REFERENCE => $order->getOrderIncrementId(),
                self::TITLE => $billingAddress->getPrefix(),
                self::FIRST_NAME => $billingAddress->getFirstname(),
                self::LAST_NAME => $billingAddress->getLastname(),
                self::COMPANY_NAME => $billingAddress->getCompany(),
                self::STREET_1 => $billingAddress->getStreetLine1(),
                self::STREET_2 => $billingAddress->getStreetLine2(),
                self::CITY => $billingAddress->getCity(),
                self::STATE => $billingAddress->getRegionCode(),
                self::POSTAL_CODE => $billingAddress->getPostcode(),
                self::COUNTRY => strtolower($billingAddress->getCountryId()),
                self::PHONE => $billingAddress->getTelephone(),
                self::EMAIL => $billingAddress->getEmail(),
            ]
        ];
    }
}
