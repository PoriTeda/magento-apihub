<?php
namespace Riki\Rma\Controller\Adminhtml\Refund\Bank;

use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

class Complete extends \Riki\Rma\Controller\Adminhtml\Refund
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_refund_actions_complete_bank';

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
                $this->refundManagement->completeByBank($id);
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

        if ($successCount) {
            $this->messageManager->addSuccess($successCount == 1
                ? __('You completed the refund by Bank Transfer successfully.')
                : __('You completed %1 refund(s) by Bank Transfer successfully.', $successCount)
            );
        }

        return $result;
    }
}