<?php
namespace Riki\Sales\Controller\Adminhtml\ShippingReason;

use Riki\Sales\Controller\Adminhtml\ShippingReason\Reason;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Sales\Model\ShippingReasonData;

class Delete extends Reason
{
    /**
     * Delete the Reason entity
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $reasonId = $this->getRequest()->getParam('id');
        if ($reasonId) {
            try {
                $this->shippingReasonRepository->deleteById($reasonId);
                $this->messageManager->addSuccessMessage(__('The Shipping Reason has been deleted.'));
                $resultRedirect->setPath('riki_sales/shippingreason/index');
                return $resultRedirect;
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('The Shipping Reason no longer exists.'));
                return $resultRedirect->setPath('riki_sales/shippingreason/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('riki_sales/shippingreason/index', ['id' => $reasonId]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while deleting the Shipping Reason'));
                return $resultRedirect->setPath('riki_sales/shippingreason/edit', ['id' => $reasonId]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find the Shipping Reason to delete.'));
        $resultRedirect->setPath('riki_sales/shippingreason/index');
        return $resultRedirect;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingReasonData::ACL_DELETE);
    }
}
