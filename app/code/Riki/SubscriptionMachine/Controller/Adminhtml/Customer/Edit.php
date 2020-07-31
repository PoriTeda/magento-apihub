<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Customer;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\App\Action\Context;

class Edit extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineCustomerFactory
     */
    protected $machineCustomerFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Edit constructor.
     * @param Context $context
     * @param Registry $registry
     * @param \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
    ) {
        $this->machineCustomerFactory = $machineCustomerFactory;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * Edit Blacklisted
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->machineCustomerFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This blacklisted no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/');
                return $resultRedirect;
            }
        }
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->registry->register('machinecustomer_item', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_SubscriptionMachine::machine_customer');
        $resultPage->addBreadcrumb(__('Machine Customer'), __('Machine Customer'));
        $resultPage->getConfig()->getTitle()->prepend(__('Machine Customer'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Machine Customer'));
        $resultPage->addBreadcrumb(
            $id ? __('Edit Machine Customer') : __('New Machine Customer'),
            $id ? __('Edit Machine Customer') : __('New Machine Customer')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Machine Customer') : __('New Machine Customer')
        );
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_customer_save');
    }
}
