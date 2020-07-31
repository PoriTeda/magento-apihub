<?php

namespace Bluecom\PaymentFee\Controller\Adminhtml\Payment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
class Edit extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Result page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /* @var \Bluecom\PaymentFee\Model\PaymentFeeFactory */
    protected $paymentFeeFactory;

    /**
     * Edit constructor.
     *
     * @param Context                                    $context           context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory result page factory
     * @param \Magento\Framework\Registry                $registry          registry
     */
    public function __construct(
        Context $context,
        \Bluecom\PaymentFee\Model\PaymentFeeFactory $paymentFeeFactory,
        PageFactory $resultPageFactory,
        Registry $registry
    ) {
        $this->paymentFeeFactory = $paymentFeeFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Edit Payment Fee
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        //Get ID and create model
        $entityId = $this->getRequest()->getParam('entity_id');
        $model = $this->paymentFeeFactory->create();

        if ($entityId) {
            $model->load($entityId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This payment fee no longer exists.'));
                /**
                 * Result redirect
                 *
                 * \Magento\Backend\Model\View\Result\Redirect $resultRedirect result redirect
                 */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        //Set entered data if was error when do save
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        //Register model to user later in blocks
        $this->_coreRegistry->register('payment_fee', $model);

        //Build edit form
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $entityId ? __('Edit Payment Fee') : __('New Payment Fee'),
            $entityId ? __('Edit Payment Fee') : __('New Payment Fee')
        );

        $resultPage->getConfig()->getTitle()->prepend(__('Payment Fee'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getPaymentName() : __('New Payment Fee'));

        return $resultPage;
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /* @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Bluecom_PaymentFee::index')
            ->addBreadcrumb(__('Payment Fee'), __('Method'))
            ->addBreadcrumb(__('Manage Payment Fee'), __('Manage Payment Fee'));

        return $resultPage;
    }

    /**
     * Set allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluecom_PaymentFee::index');
    }
}