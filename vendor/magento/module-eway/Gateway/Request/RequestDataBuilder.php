<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class RequestDataBuilder
 */
class RequestDataBuilder implements BuilderInterface
{
    /**
     * Safe transaction type
     *
     * This is the default transaction type and refers to a standard eCommerce
     * transaction using CVN and 3D Secure if available
     */
    const PURCHASE = 'Purchase';

    /**
     * The customer’s IP address
     *
     * WARNING:
     * When this field is present along with the Customer Country field,
     * any transaction will be processed using Beagle Fraud Alerts
     */
    const CUSTOMER_IP = 'CustomerIP';

    /**
     * The action to perform with this request
     *
     * One of: ProcessPayment, CreateTokenCustomer, UpdateTokenCustomer, TokenPayment, Authorise
     *
     * @link https://eway.io/api-v3/#payment-methods
     */
    const METHOD = 'Method';

    /**
     * The type of transaction you’re performing
     *
     * One of: Purchase, MOTO, Recurring
     *
     * @link https://eway.io/api-v3/#transaction-types
     */
    const TRANSACTION_TYPE = 'TransactionType';

    /**
     * The identification name/number for the device or application used to process the transaction
     */
    const DEVICE_ID = 'DeviceID';

    /**
     * The partner ID generated from an eWAY partner agreement
     */
    const PARTNER_ID = 'PartnerID';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var array map of payment methods
     */
    private $paymentMethods = [
        AbstractMethod::ACTION_AUTHORIZE => 'Authorise',
        AbstractMethod::ACTION_AUTHORIZE_CAPTURE => 'ProcessPayment',
    ];

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        return [
            self::METHOD => $this->getPaymentMethod(
                $this->config->getValue('payment_action', $order->getStoreId())
            ),
            self::CUSTOMER_IP => $order->getRemoteIp(),
            self::TRANSACTION_TYPE => self::PURCHASE
        ];
    }

    /**
     * @param string $paymentAction
     * @return string
     */
    private function getPaymentMethod($paymentAction)
    {
        return $this->paymentMethods[$paymentAction];
    }
}
