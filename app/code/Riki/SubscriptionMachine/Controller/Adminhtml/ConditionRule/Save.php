<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\ConditionRule;

use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Save extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory
     */
    protected $machineConditionRuleFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $machineConditionRuleFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        LoggerInterface $logger,
        \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $machineConditionRuleFactory
    ) {
        $this->machineConditionRuleFactory = $machineConditionRuleFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Save winner prize
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $redirectBack = $this->getRequest()->getParam('back', false);
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('machine/conditionRule');
        }
        try {
            /** @var \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $model */
            $model = $this->machineConditionRuleFactory->create();
            if (!empty($data['id'])) {
                $model = $model->load($data['id']);
                if (!$model->getId()) {
                    $this->messageManager->addError(__('This machine customer no longer exists.'));
                    /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/');
                    return $resultRedirect;
                }
            }
            if (isset($data['course_code'])) {
                $data['course_code'] = json_encode($data['course_code']);
            }
            if (isset($data['frequency'])) {
                $data['frequency'] = json_encode($data['frequency']);
            }
            if (isset($data['category_id'])) {
                $data['category_id'] = $data['category_id'][0];
            }
            $data['payment_method'] = isset($data['payment_method']) ? json_encode($data['payment_method']) : null;

            $model->setData($data);
            $model->save();
            $this->messageManager->addSuccess(__('Machine condition rule has been saved successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->error($e);
            $redirectBack = true;
            $this->_session->setFormData($data);
        }
        return $redirectBack
            ? $this->_redirect('machine/conditionRule/edit', [
                'id' => $model->getId()
            ])
            : $this->_redirect('machine/conditionRule');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_conditionRule_save');
    }
}
