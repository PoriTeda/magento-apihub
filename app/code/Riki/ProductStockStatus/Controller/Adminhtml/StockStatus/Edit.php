<?php
/**
 * ProductStockStatus Edit
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
use Riki\ProductStockStatus\Block\Adminhtml\StockStatus;

/**
 * Class Edit
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Edit extends StockStatusAbstract
{
    /**
     * Edit Stock Status
     *
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('status_id');
        $model = $this->stockStatusFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This status no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->registry->register('stockdisplay_stockstatus', $model);
        $this->registry->register('current_stockstatus', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Status') : __('New Status'),
            $id ? __('Edit Status') : __('New Status')
        );
        
        $resultPage->getConfig()->getTitle()->prepend(
                $model->getId() ? __('Edit Status') : __('New Status')
            );

        return $resultPage;
    }
}