<?php

namespace Riki\MachineApi\Controller\Adminhtml\B2c;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Delete extends \Riki\MachineApi\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * Delete constructor.
     * @param Context $context
     * @param \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
     */
    public function __construct(
        Context $context,
        \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
    ) {
        parent::__construct($context);
        $this->machineTypeFactory = $machineTypeFactory;
    }

    /**
     * Delete winner prize
     *
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('type_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->machineTypeFactory->create();
                $model->load($id);
                if (!$model->getId()) {
                    throw new LocalizedException(__('This machine type no longer exists.'));
                }
                if (!$model->canDelete()) {
                    throw new LocalizedException(__('Could not delete, data is being used!'));
                }
                $model->delete();
                $this->messageManager->addSuccess(__('The machine type has been deleted.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('An error occurs.'));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::machine_b2c_skus_delete');
    }
}
