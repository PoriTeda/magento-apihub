<?php

namespace Riki\Rma\Controller\Adminhtml\Returns\Request;

class Accept extends \Riki\Rma\Controller\Adminhtml\Returns
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return_actions_accept_request';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initRedirectResult();
        $request = $this->getRequest();
        $requestData = $this->getRequest()->getPostValue();
        $id = $request->getParam('id', 0);
        if ($id) {
            $result->setUrl($this->getUrl('adminhtml/rma/edit', ['id' => $id]));
        } else {
            $result->setUrl($this->getUrl('adminhtml/rma/'));
        }

        // Validate total return amount
        if (isset($requestData['total_return_amount_adjusted'])) {
            $resultValidation = $this->validateTotalReturnAmount($requestData['total_return_amount_adjusted']);
            if (!$resultValidation){
                return $result;
            }
        }

        try {
            $this->rmaManagement->acceptRequest($id);
            $this->messageManager->addSuccess(__('You accepted the return request successfully.'));
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

        return $result;
    }

    /**
     * Validate total return amount
     * @param $amount
     * @return bool
     */
    private function validateTotalReturnAmount($amount)
    {
        if ((float)$amount < 0) {
            $this->messageManager->addError(__('Final return / Refund amount is not be a negative value'));
            return false;
        }

        return true;
    }
}