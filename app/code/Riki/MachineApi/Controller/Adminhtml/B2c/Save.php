<?php
namespace Riki\MachineApi\Controller\Adminhtml\B2c;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Save extends \Riki\MachineApi\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * @var \Magento\Backend\Helper\Js
     */
    protected $jsHelper;
    protected $logger;

    /**
     * Save constructor.
     * @param Context $context
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param \Magento\Backend\Helper\Js $jsHelper
     * @param \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LoggerInterface $logger,
        \Magento\Backend\Helper\Js $jsHelper,
        \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
    ) {
        $this->machineTypeFactory = $machineTypeFactory;
        $this->logger = $logger;
        $this->jsHelper = $jsHelper;
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
            return $this->_redirect('machine/b2c/new');
        }
        try {
            /** @var \Riki\MachineApi\Model\B2CMachineSkus $model */
            $model = $this->machineTypeFactory->create();
            if (!empty($data['type_id'])) {
                $model = $model->load($data['type_id']);
                if (!$model->getId()) {
                    $this->messageManager->addError(__('This machine type no longer exists.'));
                    /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/');
                    return $resultRedirect;
                }
            }

            $data = $this->setMachineData($data);

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
                $this->messageManager->addSuccess(__('The B2C machine SKUs has been saved successfully.'));
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
            ? $this->_redirect('machine/b2c/edit', [
                'type_id' => $model->getId()
            ])
            : $this->_redirect('machine/b2c');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::machine_b2c_skus_save');
    }

    /**
     * Set Machine Data
     *
     * @param array $data
     *
     * @return array $data
     */
    protected function setMachineData($data)
    {
        if (isset($data['machines'])) {
            $data['machines'] = $this->jsHelper->decodeGridSerializedInput($data['machines']);
            // mapping grid view data
            foreach ($data['machines'] as $itemId => $machine) {
                if (isset($machine['product_machine'])) {
                    $data['machines'][$itemId]['is_free'] = $machine['product_machine'];
                }
                if (isset($machine['wbs'][$itemId])) {
                    $data['machines'][$itemId]['wbs'] = $machine['wbs'][$itemId];
                }
            }
        }
        return $data;
    }
}
