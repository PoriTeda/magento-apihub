<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma;

use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

/**
 * Class CreditMemo
 *
 * @package Riki\Rma\Plugin\Rma\Model\Rma
 * @deprecated
 */
class CreditMemo
{
    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * @var \Riki\Rma\Plugin\Paygent\Helper\Data
     */
    protected $paygentDataPlugin;

    /**
     * CreditMemo constructor.
     *
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Message\Manager $messageManager
     * @param \Riki\Rma\Plugin\Paygent\Helper\Data $paygentDataPlugin
     */
    public function __construct(
        \Riki\Rma\Helper\Refund $refundHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\Manager $messageManager,
        \Riki\Rma\Plugin\Paygent\Helper\Data $paygentDataPlugin
    ) {
        $this->refundHelper = $refundHelper;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->paygentDataPlugin = $paygentDataPlugin;
    }

    /**
     * Create credit memo
     *
     * @param \Magento\Rma\Model\Rma $subject
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeBeforeSave(\Magento\Rma\Model\Rma $subject)
    {
        if (!$subject->dataHasChangedFor('refund_status')) {
            return [];
        }

        if ($subject->getData('refund_status') == RefundStatusInterface::APPROVED
            && $subject->getData('refund_method') != \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            return [];
        }

        $trigger = [
            RefundStatusInterface::APPROVED,
            RefundStatusInterface::BT_COMPLETED,
            RefundStatusInterface::CHECK_ISSUED
        ];

        if (!in_array($subject->getData('refund_status'), $trigger)) {
            return [];
        }

        $this->paygentDataPlugin->setRma($subject);

        try {
            $creditMemo = $this->refundHelper->refund($subject);
            if (!$creditMemo->getEntityId()) {
                if ($subject->getData('refund_status') == RefundStatusInterface::APPROVED) {
                    $customer = $subject->getCustomer();
                    $offlineCustomerAttr = $customer->getCustomAttribute('offline_customer');
                    $isOfflineCustomer = is_object($offlineCustomerAttr) ? $offlineCustomerAttr->getValue() : 0;
                    if ($isOfflineCustomer == 0) {
                        $refundStatus = RefundStatusInterface::CHANGE_TO_BANK;
                        $subject->setRefundMethod(
                            \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
                        );
                    } else {
                        $refundStatus = RefundStatusInterface::CHANGE_TO_CHECK;
                    }
                    $subject->setData('refund_status', $refundStatus);
                    return [];
                }
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Trigger credit memo failed, please try again')
                );
            }

            $subject->setData('creditmemo_id', $creditMemo->getEntityId());
            $subject->setData('creditmemo_increment_id', $creditMemo->getIncrementId());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e);
            if ($subject->getData('refund_status') == RefundStatusInterface::APPROVED) {
                $customer = $subject->getCustomer();
                $offlineCustomerAttr = $customer->getCustomAttribute('offline_customer');
                $isOfflineCustomer = is_object($offlineCustomerAttr) ? $offlineCustomerAttr->getValue() : 0;
                if ($isOfflineCustomer == 0) {
                    $refundStatus = RefundStatusInterface::CHANGE_TO_BANK;
                    $subject->setRefundMethod(
                        \Magento\OfflinePayments\Model\Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE
                    );
                } else {
                    $refundStatus = RefundStatusInterface::CHANGE_TO_CHECK;
                }
                $subject->setData('refund_status', $refundStatus);
                $this->messageManager->addError($e->getMessage());
            }
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Trigger credit memo failed, please try again')
            );
        }

        if ($subject->getData('refund_status') == RefundStatusInterface::APPROVED) {
            $subject->setData('refund_status', RefundStatusInterface::CARD_COMPLETED);
        }

        return [];
    }
}