<?php
/**
 * ProductStockStatus Delete
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
/**
 * Class Delete
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Delete extends StockStatusAbstract
{
    /**
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('status_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->stockStatusFactory->create();
                $model->load($id);
                $model->delete();
                //update product attribute value
                $this->updateProduct->updateAttributeValue($id);
                $this->messageManager->addSuccess(__('The status has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['status_id' => $id]);
            }
        }
        $this->messageManager->addError(__('This status no longer exists.'));
        return $resultRedirect->setPath('*/*/');
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ProductStockStatus::delete');
    }
}
