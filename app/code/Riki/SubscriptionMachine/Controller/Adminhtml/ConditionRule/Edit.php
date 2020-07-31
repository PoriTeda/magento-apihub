<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\ConditionRule;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\App\Action\Context;

class Edit extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory
     */
    protected $machineConditionRuleFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Edit constructor.
     * @param Context $context
     * @param Registry $registry
     * @param \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $machineConditionRuleFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $machineConditionRuleFactory
    ) {
        $this->machineConditionRuleFactory = $machineConditionRuleFactory;
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
        $model = $this->machineConditionRuleFactory->create();

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
        $this->registry->register('machinecondition_item', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_SubscriptionMachine::machine_conditionRule');
        $resultPage->addBreadcrumb(__('Machine Customer'), __('Machine Condition Rule'));
        $resultPage->getConfig()->getTitle()->prepend(__('Machine Condition Rule'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Machine Condition Rule'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Machine Condition Rule'): __('New Machine Condition Rule')
        );
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_conditionRule_save');
    }
}
