<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\ConditionRule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory
     */
    protected $machineConditionRuleFactory;

    /**
     * Delete constructor.
     *
     * @param Context $context
     * @param \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $machineConditionRuleFactory
     */
    public function __construct(
        Context $context,
        \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $machineConditionRuleFactory
    ) {
        parent::__construct($context);
        $this->machineConditionRuleFactory = $machineConditionRuleFactory;
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
                $model = $this->machineConditionRuleFactory->create();
                $model->load($id);
                if (!$model->getId()) {
                    throw new LocalizedException(__('This machine condition rule no longer exists.'));
                }
                $model->delete();
                $this->messageManager->addSuccess(__('The machine condition rule has been deleted.'));
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
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_conditionRule_delete');
    }
}
