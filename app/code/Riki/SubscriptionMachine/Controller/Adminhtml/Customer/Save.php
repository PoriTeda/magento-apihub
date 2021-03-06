<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Customer;

use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Backend\App\Action\Context;

class Save extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineCustomerFactory
     */
    protected $_machineCustomerFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Save constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LoggerInterface $logger,
        \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomerFactory
    ) {
        $this->_machineCustomerFactory = $machineCustomerFactory;
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
            return $this->_redirect('machine/customer/new');
        }
        try {
            /** @var \Riki\SubscriptionMachine\Model\MachineCustomer $model */
            $model = $this->_machineCustomerFactory->create();
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
            $model->setData($data);
            $model->save();
            $this->messageManager->addSuccess(__('The machine customer has been saved successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->error($e);
            $redirectBack = true;
            $this->_session->setFormData($data);
        }
        return $redirectBack
            ? $this->_redirect('machine/customer/edit', [
                'id' => $model->getId()
            ])
            : $this->_redirect('machine/customer');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_customer_save');
    }
}
