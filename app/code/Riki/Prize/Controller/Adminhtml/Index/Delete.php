<?php

namespace Riki\Prize\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Delete extends \Riki\Prize\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $_prizeFactory;

    /**
     * Delete constructor.
     *
     * @param Context $context
     * @param \Riki\Prize\Model\PrizeFactory $prizeFactory
     */
    public function __construct(
        Context $context,
        \Riki\Prize\Model\PrizeFactory $prizeFactory
    )
    {
        parent::__construct($context);
        $this->_prizeFactory = $prizeFactory;
    }

    /**
     * Delete winner prize
     *
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                /** @var \Riki\Prize\Model\Prize $model */
                $model = $this->_prizeFactory->create();
                $model->load($id);
                if (!$model->getId()) {
                    throw new LocalizedException(__('This prize no longer exists.'));
                }
                if (!$model->canDelete()) {
                    throw new LocalizedException(__('Could not delete, data is being used!'));
                }
                $model->delete();
                $this->messageManager->addSuccess(__('The prize has been deleted.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, 'An error occurs.');
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
