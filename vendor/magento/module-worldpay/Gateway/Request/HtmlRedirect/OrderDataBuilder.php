<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Gateway\Request\HtmlRedirect;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class OrderDataBuilder
 */
class OrderDataBuilder implements BuilderInterface
{
    /**
     * Your own reference number for this purchase. It is returned to you along
     * with the authorisation results by whatever method you have chosen for
     * being informed (email and / or Payment Responses).
     */
    const CART_ID = 'cartId';

    /**
     * A decimal number giving the cost of the purchase in terms of the major
     * currency unit e.g. 12.56 would mean 12 pounds and 56 pence if the
     * currency were GBP (Pounds Sterling). Note that the decimal separator
     * must be a dot (.), regardless of the typical language convention for the
     * chosen currency. The decimal separator does not need to be included if
     * the amount is an integral multiple of the major currency unit. Do not
     * include other separators, for example between thousands.
     */
    const AMOUNT = 'amount';

    /**
     * ISO 639
     */
    const LANGUAGE = 'lang';

    /**
     * 3 letter ISO code for the currency of this payment.
     */
    const CURRENCY = 'currency';

    /**
     * Order increment id in the Magento system
     */
    const ORDER_ID = 'MC_order_id';

    /**
     * ID store the current order
     */
    const STORE_ID = 'MC_store_id';

    /**
     * Your Worldpay Installation ID. This is a unique 6-digit
     * reference number we assign to you. It tells us which payment
     * methods and currencies your installation supports.
     */
    const INSTALLATION_ID = 'instId';

    /**
     * This specifies the authorisation mode to use. If there is
     * no merchant code with a matching authMode then
     * the transaction is rejected. The values are "A" for a full
     * auth, or "E" for a pre-auth. In the payment result this
     * parameter can also take the value "O" when
     * performing a post-auth.
     */
    const AUTH_MODE = 'authMode';

    /**
     * The shopper's full name, including any title, personal
     * name and family name.
     * Note that if you do not pass through a name, and use
     * Payment Responses, the name that the cardholder
     * enters on the payment page is returned to you as the
     * value of name in the Payment Responses message.
     * Also note that if you are sending a test submission you
     * can specify the type of response you want from our
     * system by entering REFUSED, AUTHORISED, ERROR or
     * CAPTURED as the value in the name parameter. You
     * can also generate an AUTHORISED response by using a
     * real name, such as, J. Bloggs.
     */
    const NAME = 'name';

    /**
     * The first line of the shopper's address. Encode newlines
     * as "&#10;" (the HTML entity for ASCII 10, the new line
     * character).
     * If this is not supplied in the order details then it must
     * be entered in the payment pages by the shopper
     */
    const ADDRESS_1 = 'address1';

    /**
     * The first line of the shopper's address. Encode newlines
     * as "&#10;" (the HTML entity for ASCII 10, the new line
     * character).
     */
    const ADDRESS_2 = 'address2';

    /**
     * The first line of the shopper's address. Encode newlines
     * as "&#10;" (the HTML entity for ASCII 10, the new line
     * character).
     */
    const ADDRESS_3 = 'address3';

    /**
     * The town or city. Encode newlines as "&#10;" (the
     * HTML entity for ASCII 10, the new line character).
     * If this is not supplied in the order details then it must
     * be entered in the payment pages by the shopper.
     */
    const TOWN = 'town';

    /**
     * The shopper’s region/county/state. Encode newlines as
     * "&#10;" (the HTML entity for ASCII 10, the new line
     * character).
     */
    const REGION = 'region';

    /**
     * The shopper's postcode.
     * Note that at your request we can assign mandatory
     * status to this parameter. That is, if it is not supplied in
     * the order details then the shopper must enter it in the
     * payment pages.
     */
    const POSTCODE = 'postcode';

    /**
     * The shopper's country, as 2-character ISO code,
     * uppercase.
     * If this is not supplied in the order details then it must
     * be entered in the payment pages by the shopper.
     */
    const COUNTRY = 'country';

    /**
     * The shopper's telephone number.
     */
    const TELEPHONE = 'tel';

    /**
     * The shopper's email address.
     */
    const EMAIL = 'email';

    /**
     * Using the fixContact parameter locks the address information passed to us, so that your shoppers
     * cannot change this information when they reach the payment pages, as shown in the example below.
     */
    const FIX_CONTACT = 'fixContact';

    /**
     * Alternatively, you can use the hideContact parameter to hide the address information of shoppers on
     * the payment pages.
     */
    const HIDE_CONTACT = 'hideContact';

    /**
     * A value of 100 specifies that this is a test payment.
     * Specify the test result you want by entering REFUSED,
     * AUTHORISED, ERROR, or CAPTURED in the name
     * parameter.
     * When you submit order details using the testMode
     * parameter and the URL for the live Production
     * Environment, you are presented with a page asking you
     * if you want to redirect the order details to the Test
     * Environment – select the Redirect button if you do.
     * If you submit the order details to the live production
     * environment our systems attempt to debit merchant
     * codes (accounts).
     * Reversing transactions such as these, and adjusting
     * accounts, causes unnecessary work for us as well as
     * you.
     * Set this parameter to 0 (zero) or omit it for a live
     * transaction.
     */
    const TEST_MODE = 'testMode';

    /**
     * If present, this causes the currency drop down to be hidden, so fixing
     * the currency that the shopper must purchase in.
     */
    const HIDE_CURRENCY = 'hideCurrency';

    /**
     * The value set in test mode
     */
    const TEST_MODE_VALUE = '100';

    /**
     * The value set in live mode
     */
    const LIVE_MODE_VALUE = '0';

    /**
     * The URL to process the response from gateway
     */
    const PAYMENT_CALLBACK = 'MC_callback';

    /**
     * Response url
     */
    const RESPONSE_URL = 'worldpay/htmlRedirect/response';

    /**
     * The glue for the signature fields
     */
    const GLUE = ':';

    /**
     * The field name for the signature
     */
    const SIGNATURE = 'signature';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlHelper;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * Constructor
     *
     * @param ConfigInterface $config
     * @param UrlInterface $urlHelper
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        ConfigInterface $config,
        UrlInterface $urlHelper,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->urlHelper = $urlHelper;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();

        $address = $order->getBillingAddress();

        $result = [
            self::CART_ID => $order->getOrderIncrementId(),
            self::AMOUNT => sprintf('%.2F', $order->getGrandTotalAmount()),
            self::CURRENCY => $order->getCurrencyCode(),
            self::ORDER_ID => $order->getId(),
            self::STORE_ID => $storeId,

            self::INSTALLATION_ID => $this->config->getValue('installation_id', $storeId),
            self::AUTH_MODE =>
                $this->config->getValue('payment_action', $storeId) === AbstractMethod::ACTION_AUTHORIZE
                    ? 'E'
                    : 'A',

            self::LANGUAGE => substr($this->localeResolver->getLocale(), 0, 2),

            self::HIDE_CURRENCY => true,
            self::FIX_CONTACT => !(bool)(int)$this->config->getValue('fix_contact', $storeId),
            self::HIDE_CONTACT => (bool)(int)$this->config->getValue('hide_contact', $storeId),

            self::PAYMENT_CALLBACK => $this->urlHelper->getUrl(self::RESPONSE_URL)
        ];

        if (!empty($address)) {
            $result[self::NAME] = $this->getName($address);
            $result[self::ADDRESS_1] = $address->getStreetLine1();
            $result[self::ADDRESS_2] = $address->getStreetLine2();
            $result[self::TOWN] = $address->getCity();
            $result[self::REGION] = $address->getRegionCode();
            $result[self::POSTCODE] = $address->getPostcode();
            $result[self::COUNTRY] = $address->getCountryId();
            $result[self::TELEPHONE] = $address->getTelephone();
            $result[self::EMAIL] = $address->getEmail();
        }

        if ((bool)(int)$this->config->getValue('sandbox_flag', $storeId)) {
            $result[self::NAME] = $this->config->getValue('test_action', $storeId);
            $result[self::TEST_MODE] = self::TEST_MODE_VALUE;
        } else {
            $result[self::TEST_MODE] = self::LIVE_MODE_VALUE;
        }

        $result[self::SIGNATURE] = $this->getSignature($result, $storeId);

        return [
            'fields' => array_filter(
                $result,
                function ($value) {
                    return !is_bool($value) || $value === true;
                }
            ),
            'action' => (bool)(int)$this->config->getValue('sandbox_flag', $storeId)
                ? $this->config->getValue('gateway_url_test', $storeId)
                : $this->config->getValue('gateway_url', $storeId)
        ];
    }

    /**
     * Get full customer name
     *
     * @param AddressAdapterInterface $address
     * @return string
     */
    private function getName(AddressAdapterInterface $address)
    {
        $name = '';
        if ($address->getPrefix()) {
            $name .= $address->getPrefix() . ' ';
        }
        $name .= $address->getFirstname();
        if ($address->getMiddlename()) {
            $name .= ' ' . $address->getMiddlename();
        }
        $name .= ' ' . $address->getLastname();
        if ($address->getSuffix()) {
            $name .= ' ' . $address->getSuffix();
        }
        return $name;
    }

    /**
     * Returns signature
     *
     * @param array $request
     * @param int $storeId
     * @return null|string
     */
    private function getSignature(array $request, $storeId)
    {
        $secret = $this->config->getValue('md5_secret', $storeId);

        $fieldsToSign =  explode(
            self::GLUE,
            (string)$this->config->getValue('signature_fields', $storeId)
        );

        if (!$secret || !$fieldsToSign) {
            return null;
        }

        $sign = [];
        foreach ($fieldsToSign as $field) {
            if (array_key_exists($field, $request)) {
                $sign[] = $request[$field];
            } else {
                throw new \LogicException(
                    sprintf(
                        'Field %s is not present in request to build a signature',
                        $field
                    )
                );
            }
        }

        array_unshift($sign, $secret);

        return hash('md5', implode($sign, self::GLUE));
    }
}
