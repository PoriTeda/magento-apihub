<?php
namespace Riki\Rma\Controller\Adminhtml\Refund;

class Reject extends \Riki\Rma\Controller\Adminhtml\Refund
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
                $this->refundManagement->rejectWithoutAdj($id);
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
                ? __('You rejected the refund successfully.')
                : __('You rejected %1 refund(s) successfully.', $successCount)
            );
        }

        return $result;
    }
}