<?php
namespace Riki\Rma\Controller\Adminhtml\Refund\Bank;

class Processing extends \Riki\Rma\Controller\Adminhtml\Refund
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_refund_actions_processing_bank';

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
                $this->refundManagement->processByBank($id);
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
                ? __('You processed the refund by Bank Transfer successfully.')
                : __('You processed %1 refund(s) by Bank Transfer successfully.', $successCount)
            );
        }

        return $result;
    }
}