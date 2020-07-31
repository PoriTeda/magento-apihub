<?php

namespace Riki\SubscriptionMachine\Controller\Adminhtml\Skus;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Save extends \Riki\SubscriptionMachine\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineSkusFactory
     */
    protected $_machineSkusFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkusFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        LoggerInterface $logger,
        \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkusFactory
    ) {
        $this->_machineSkusFactory = $machineSkusFactory;
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
            return $this->_redirect('machine/skus/new');
        }
        try {
            /** @var \Riki\SubscriptionMachine\Model\MachineSkus $model */
            $model = $this->_machineSkusFactory->create();
            if (!empty($data['id'])) {
                $model = $model->load($data['id']);
                if (!$model->getId()) {
                    $this->messageManager->addError(__('This machine SKUs no longer exists.'));
                    /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/');
                    return $resultRedirect;
                }
            }
            $model->setData($data);

            $errors = $model->validate();

            if ($errors !== true) {
                foreach ($errors as $error) {
                    $this->messageManager->addError($error);
                }

                $redirectBack = true;
                $this->_session->setFormData($data);
            } else {
                $model->save();
                $this->messageManager->addSuccess(__('The machine SKUs has been saved successfully.'));
            }
        } catch (\Exception $e) {
            if ($e instanceof LocalizedException) {
                $this->messageManager->addError($e->getMessage());
            } else {
                $this->messageManager->addException($e, __('An error occurs.'));
            }

            $redirectBack = true;
            $this->_session->setFormData($data);
        }

        return $redirectBack
            ? $this->_redirect('machine/skus/edit', [
                'id' => $model->getId()
            ])
            : $this->_redirect('machine/skus');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionMachine::machine_skus_save');
    }
}
