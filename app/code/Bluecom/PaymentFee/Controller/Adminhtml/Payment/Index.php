<?php

namespace Bluecom\PaymentFee\Controller\Adminhtml\Payment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Page factory
     *
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Init
     *
     * @param Context     $context           context
     * @param PageFactory $resultPageFactory result page factory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return string
     */
    public function execute()
    {
        /**
         * Result page
         *
         * @var \Magento\Backend\Model\View\Result\Page $resultPage result page
         */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bluecom_PaymentFee::payment');
        $resultPage->getConfig()->getTitle()->prepend(__('Payment Fee'));

        return $resultPage;
    }

    /**
     * Is allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluecom_PaymentFee::index');
    }
}