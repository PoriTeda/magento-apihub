<?php
namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Riki\Sales\Controller\Adminhtml\ShippingCause\Cause;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Sales\Model\ShippingCauseData;

class Delete extends Cause
{
    /**
     * Delete the Cause entity
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $causeId = $this->getRequest()->getParam('id');
        if ($causeId) {
            try {
                $this->shippingCauseRepository->deleteById($causeId);
                $this->messageManager->addSuccessMessage(__('The Shipping Cause has been deleted.'));
                $resultRedirect->setPath('riki_sales/shippingcause/index');
                return $resultRedirect;
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('The Shipping Cause no longer exists.'));
                return $resultRedirect->setPath('riki_sales/shippingcause/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('riki_sales/shippingcause/index', ['id' => $causeId]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while deleting the Shipping Cause'));
                return $resultRedirect->setPath('riki_sales/shippingcause/edit', ['id' => $causeId]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find the Shipping Cause to delete.'));
        $resultRedirect->setPath('riki_sales/shippingcause/index');
        return $resultRedirect;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingCauseData::ACL_DELETE);
    }
}
