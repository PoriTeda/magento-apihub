<?php

namespace Bluecom\PaymentFee\Controller\Adminhtml\Payment;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Bluecom\PaymentFee\Model\ResourceModel\PaymentFee\CollectionFactory;


class MassEnable extends \Magento\Backend\App\Action
{
    /**
     * Filter
     *
     * @var Filter
     */
    protected $_filter;
    /**
     * Collection
     *
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * MassEnable constructor.
     *
     * @param Context           $context           context
     * @param Filter            $filter            filter
     * @param CollectionFactory $collectionFactory collection factory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());

        foreach ($collection as $item) {
            $item->setActive(true);
            try {
                $item->save();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been enabled.', $collection->getSize()));

        /**
         * Redirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect redirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
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