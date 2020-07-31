<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Skus;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

class Edit extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineSkusFactory
     */
    protected $machineSkusFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Edit constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkusFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkusFactory
    ) {
        $this->machineSkusFactory = $machineSkusFactory;
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
        $model = $this->machineSkusFactory->create();

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
        $this->registry->register('machineskus_item', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_SubscriptionMachine::machine_skus');
        $resultPage->addBreadcrumb(__('Machine SKUs'), __('Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Machine SKUs'));
        $resultPage->addBreadcrumb(
            $id ? __('Edit Machine SKUs') : __('New Machine SKUs'),
            $id ? __('Edit Machine SKUs') : __('New Machine SKUs')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Machine SKUs') : __('New Machine SKUs')
        );
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_skus_save');
    }
}
