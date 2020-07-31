<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Delete extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineCustomerFactory
     */
    protected $machineCustomerFactory;

    /**
     * Delete constructor.
     *
     * @param Context $context
     * @param \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
     */
    public function __construct(
        Context $context,
        \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
    ) {
        parent::__construct($context);
        $this->machineCustomerFactory = $machineCustomerFactory;
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
                $model = $this->machineCustomerFactory->create();
                $model->load($id);
                if (!$model->getId()) {
                    throw new LocalizedException(__('This machine customer no longer exists.'));
                }
                if (!$model->canDelete()) {
                    throw new LocalizedException(__('Could not delete, data is being used!'));
                }
                $model->delete();
                $this->messageManager->addSuccess(__('The machine customer has been deleted.'));
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
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_customer_delete');
    }
}
