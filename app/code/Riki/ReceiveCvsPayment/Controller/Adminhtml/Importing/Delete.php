<?php
/**
 * Receive CVS Payment
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ReceiveCvsPayment\Controller\Adminhtml\Importing;
/**
 * Class Delete
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\ReceiveCvsPayment\Model\ResourceModel\Importing\CollectionFactory
     */
    protected $cvsModelFactory;

    /**
     * Delete constructor.
     * @param \Riki\ReceiveCvsPayment\Model\ResourceModel\Importing\CollectionFactory $collectionFactory
     */
    public function __construct
    (
        \Riki\ReceiveCvsPayment\Model\ResourceModel\Importing\CollectionFactory $collectionFactory
    )
    {
        $this->cvsModelFactory = $collectionFactory;
    }
    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('upload_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $title = "";
            try {
                // init model and delete
                $model = $this->cvsModelFactory->create();
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('The importing has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['upload_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a importing to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ReceiveCvsPayment::importing');
    }
}
