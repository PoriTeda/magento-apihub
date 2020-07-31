<?php

namespace Riki\Rma\Controller\Adminhtml\Returns;

use Riki\NpAtobarai\Exception\ApproveRmaNpAtobaraiException;
use Riki\NpAtobarai\Exception\NotRefundPaidTransactionException;

class Approve extends \Riki\Rma\Controller\Adminhtml\Returns
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return_actions_approve';

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
                $isRejected = false;
                $this->rmaManagement->approve($id);
                $successCount++;
            } catch (ApproveRmaNpAtobaraiException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (NotRefundPaidTransactionException $e) {
                $isRejected = true;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $this->messageManager->addError(__(
                    'Cannot approve RMA #%1. %2',
                    $rma->getIncrementId(),
                    $e->getMessage()
                ));
            } catch (\Exception $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $this->messageManager->addError(__(
                    'An error occurred when processing item %1, please try again.',
                    $rma->getIncrementId()
                ));
                $this->logger->critical($e);
            }

            if ($isRejected) {
                try {
                    $message = __('The return status was changed to CS feedback - Rejected as it was already paid');
                    // Call rejected by CS and show error message
                    $this->getRequest()->setParams([
                        'comment' => [
                            'comment' => $message
                        ]
                    ]);
                    $this->rmaManagement->reject($id);
                    $this->messageManager->addError($message);
                } catch (\Exception $e) {
                    $rma = $this->rmaManagement->getLastProceedRma();
                    $this->messageManager->addError(__(
                        'An error occurred when processing item %1, please try again.',
                        $rma->getIncrementId()
                    ));
                    $this->logger->critical($e);
                }
            }
        }

        if ($successCount) {
            $this->messageManager->addSuccess(
                $successCount == 1
                ? __('You approved the return successfully.')
                : __('You approved %1 return(s) successfully.', $successCount)
            );
        }

        return $result;
    }
}
