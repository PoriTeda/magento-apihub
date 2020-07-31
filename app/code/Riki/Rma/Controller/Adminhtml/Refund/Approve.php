<?php
namespace Riki\Rma\Controller\Adminhtml\Refund;

use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

class Approve extends \Riki\Rma\Controller\Adminhtml\Refund
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initRedirectResult();
        $request = $this->getRequest();
        $result->setUrl($this->getUrl('riki_rma/refund/'));
        $id = $request->getParam('id', 0);
        $ids = $request->getParam('entity_ids', [$id]);

        $successCount = 0;
        foreach ($ids as $id) {
            try {
                $this->refundManagement->approve($id);
                $rma = $this->refundManagement->getLastProceedRefund();
                if ($rma->getEntityId() == $id
                    && $rma->getRefundStatus() == RefundStatusInterface::CHANGE_TO_CHECK
                ) {
                    $msg = __('We can\'t create credit memo for the order.')
                        . ' RMA: ' . $rma->getIncrementId();
                    $this->messageManager->addError($msg);
                }
                $successCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $rma = $this->refundManagement->getLastProceedRefund();
                $msg = $e->getMessage() . ' RMA: '
                    . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                $this->messageManager->addError($msg);
                $this->logger->critical($e);
                $hasException = $id;

            } catch (\Exception $e) {
                $rma = $this->refundManagement->getLastProceedRefund();
                $msg = __('An error occurred when processing, please try again!') . ' RMA: '
                    . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                $this->messageManager->addError($msg);
                $this->logger->critical($e);
                $hasException = $id;
            }

            $rma = $this->refundManagement->getLastProceedRefund();
            if (isset($hasException)
                && $hasException == $id
                && $rma->getEntityId() == $id
                && $rma->getRefundMethod() == \Bluecom\Paygent\Model\Paygent::CODE
            ) {
                try {
                    $this->refundManagement->processByCheck($id);
                    // Implement the logic from NED-2227 again
                    $this->refundManagement->updateFailedCcRefund($id);
                    $successCount++;
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $rma = $this->refundManagement->getLastProceedRefund();
                    $msg = $e->getMessage() . ' RMA: '
                        . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                    $this->messageManager->addError($msg);
                    $this->logger->critical($e);
                } catch (\Exception $e) {
                    $rma = $this->refundManagement->getLastProceedRefund();
                    $msg = __('An error occurred when processing, please try again!') . ' RMA: '
                        . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                    $this->messageManager->addError($msg);
                    $this->logger->critical($e);
                }
            }
        }

        if ($successCount) {
            $this->messageManager->addSuccess($successCount == 1
                ? __('You approved the refund successfully.')
                : __('You approved %1 refund(s) successfully.', $successCount)
            );
        }

        return $result;
    }
}