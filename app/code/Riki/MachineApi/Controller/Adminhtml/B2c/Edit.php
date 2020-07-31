<?php
namespace Riki\MachineApi\Controller\Adminhtml\B2c;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

class Edit extends \Riki\MachineApi\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkusFactory
     */
    protected $b2cMachineSkusFactory;

    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(
        Context $context,
        Registry $registry,
        \Riki\MachineApi\Model\B2CMachineSkusFactory $b2cMachineSkusFactory
    ) {
        $this->b2cMachineSkusFactory = $b2cMachineSkusFactory;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * Edit Blacklisted
     *
     * @return \Magento\Backend\Model\View\Result\Page | \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('type_id');
        $model = $this->b2cMachineSkusFactory->create();

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
        $this->registry->register('b2cmachineskus_item', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_MachineApi::machine_b2c_skus');
        $resultPage->addBreadcrumb(__('B2C Machine SKUs'), __('B2C Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('B2C Machine SKUs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage B2C Machine SKUs'));
        $resultPage->addBreadcrumb(
            $id ? __('Edit B2C Machine SKUs') : __('New B2C Machine SKUs'),
            $id ? __('Edit B2C Machine SKUs') : __('New B2C Machine SKUs')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit B2C Machine SKUs') : __('New B2C Machine SKUs')
        );
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::machine_b2c_skus_save');
    }
}
