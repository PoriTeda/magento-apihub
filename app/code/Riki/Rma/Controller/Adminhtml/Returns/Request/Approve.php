<?php

namespace Riki\Rma\Controller\Adminhtml\Returns\Request;

use Riki\NpAtobarai\Exception\ApproveRmaNpAtobaraiException;

class Approve extends \Riki\Rma\Controller\Adminhtml\Returns
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return_actions_approve_request';

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
            if (!$resultValidation) {
                return $result;
            }
        }

        $ids = $request->getParam('entity_ids', [$id]);

        $successCount = 0;
        foreach ($ids as $id) {
            try {
                $this->rmaManagement->approveRequest($id);
                $successCount++;
            } catch (ApproveRmaNpAtobaraiException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $this->messageManager->addError(__(
                    'Cannot approve RMA #%1, error detail: %2',
                    $rma->getIncrementId(),
                    $e->getMessage()
                ));
            } catch (\Exception $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $this->messageManager->addError(__(
                    'An error occurred when processing item %1, please try again!',
                    $rma->getIncrementId()
                ));
                $this->logger->critical($e);
            }
        }

        if ($successCount) {
            $this->messageManager->addSuccess(
                $successCount == 1
                ? __('You approved the return request successfully.')
                : __('You approved %1 return request(s) successfully.', $successCount)
            );
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