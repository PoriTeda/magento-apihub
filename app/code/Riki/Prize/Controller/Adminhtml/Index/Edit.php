<?php
namespace Riki\Prize\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

class Edit extends \Riki\Prize\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $prizeFactory;

    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(
        Action\Context $context,
        Registry $registry,
        \Riki\Prize\Model\PrizeFactory $prizeFactory
    )
    {
        $this->prizeFactory = $prizeFactory;
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
        $id = $this->getRequest()->getParam('id');
        $model = $this->prizeFactory->create();

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
        $this->registry->register('prize_item', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_Prize::prize');
        $resultPage->addBreadcrumb(__('Prize'), __('Prize'));
        $resultPage->getConfig()->getTitle()->prepend(__('Prize'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Prize'));
        $resultPage->addBreadcrumb(
            $id ? __('Edit Prize') : __('New Prize'),
            $id ? __('Edit Prize') : __('New Prize')
        );
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Prize') : __('New Prize')
        );
        return $resultPage;
    }
}