<?php
namespace Riki\Rma\Controller\Adminhtml\Returns;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;

class Reject extends \Riki\Rma\Controller\Adminhtml\Returns
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return_actions_reject';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initRedirectResult();
        $request = $this->getRequest();
        $id = $request->getParam('id', 0);
        if ($id) {
            $result->setUrl($this->getUrl('adminhtml/rma/edit', ['id' => $id]));
        } else {
            $result->setUrl($this->getUrl('adminhtml/rma/'));
        }
        $ids = $request->getParam('entity_ids', [$id]);

        $successCount = 0;
        foreach ($ids as $id) {
            try {
                $this->rmaManagement->reject($id);
                $successCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $msg = $e->getMessage() . ' RMA: '
                    . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                $this->messageManager->addError($msg);
            } catch (\Exception $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $msg = __('An error occurred when processing, please try again!') . ' RMA: '
                    . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                $this->messageManager->addError($msg);

                $this->logger->critical($e);
            }
        }

        if ($successCount) {
            $this->messageManager->addSuccess(
                $successCount == 1
                ? __('You rejected the return successfully.')
                : __('You rejected %1 return(s) successfully.', $successCount)
            );
        }

        return $result;
    }
}
