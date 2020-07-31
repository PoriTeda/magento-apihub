<?php
/**
 * Schedules Controller
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ProductStockStatus\Controller\Adminhtml\StockStatus;
use Riki\ProductStockStatus\Model\StockStatus;

/**
 * Class Save
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Save extends StockStatusAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ProductStoctStatus::stockstatus');
    }
    /**
     * @return $this
     */
    public function execute()
    {
        $redirectBack = $this->getRequest()->getParam('back', false);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if data sent
        $data = $this->getRequest()->getPostValue();
        if ($data) {

            $id = $this->getRequest()->getParam('status_id');
            $statusModel = $this->stockStatusFactory->create();
            if ($id) {
                try {
                    $statusModel->load($id);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
            if (!$statusModel->getId() && $id) {
                $this->messageManager->addError(__('This status no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            try {

                $statusModel->addData($data)->save();
                // display success message
                $this->messageManager->addSuccess(__('You saved the status.'));
                // clear previously saved data from session
                $this->_session->setFormData(false);

            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // save data in session
                $this->_session->setFormData($data);
            }
            return $redirectBack?
                $resultRedirect->setPath('*/*/edit', ['status_id' => $statusModel->getId()])
                : $resultRedirect->setPath('*/*/');
        } else {
            $this->messageManager->addError('No data to save');
            return $resultRedirect->setPath('*/*/');
        }
    }
}
