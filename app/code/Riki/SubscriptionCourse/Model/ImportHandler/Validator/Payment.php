<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;
use Riki\SubscriptionCourse\Model\Course as SubscriptionCourse;

class Payment extends AbstractImportValidator
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $subscriptionPaymentMethods = [
        'paygent' => SubscriptionCourse::SUBSCRIPTION_PAYMENT_CREDIT_CARD,
        'cashondelivery' => SubscriptionCourse::SUBSCRIPTION_PAYMENT_COD,
        'cvspayment' => SubscriptionCourse::SUBSCRIPTION_PAYMENT_CSV,
        'invoicedbasedpayment' => SubscriptionCourse::SUBSCRIPTION_PAYMENT_INVOICE_PAYMENT
    ];

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$value['subscription_course_payment']) {
            return false;
        }

        $subPaymentIds = json_decode($value['subscription_course_payment'], true);
        $availableMethodIds = [];

        if ($subPaymentIds) {
            $paymentMethods = $this->scopeConfig->getValue(
                'payment',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
            );

            foreach ($paymentMethods as $code => $data) {
                if (isset($data['active'])
                    && (bool)$data['active']
                    && in_array($code, array_keys($this->subscriptionPaymentMethods))
                ) {
                    $availableMethodIds[] = $this->subscriptionPaymentMethods[$code];
                }
            }
        } else {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_JSON_KEY_NOT_FOUND
                        ),
                        'payment_id'
                    )
                ]
            );
            return false;
        }

        $notExistMethods = array_diff($subPaymentIds, $availableMethodIds);
        if ($notExistMethods) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_PAYMENT_NOT_FOUND
                        ),
                        implode(',', $notExistMethods)
                    )
                ]
            );
            return false;
        } else {
            return true;
        }
    }
}
