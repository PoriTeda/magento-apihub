<?php

namespace Bluecom\PaymentFee\Controller\Adminhtml\Payment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Bluecom\PaymentFee\Model\ResourceModel\PaymentFee\CollectionFactory;
use Bluecom\PaymentFee\Model\PaymentFeeFactory;
use Bluecom\PaymentFee\Model\Config\Source\Payment\AvailableMethods;

class Update extends \Magento\Backend\App\Action
{

    /**
     * Collection
     *
     * @var CollectionFactory
     */
    protected $_collectionFactory;
    /**
     * Available methods
     *
     * @var AvailableMethods
     */
    protected $_availableMethods;
    /**
     * Payment fee factory
     *
     * @var PaymentFeeFactory
     */
    protected $_paymentFeeFactory;

    /**
     * Update constructor.
     *
     * @param Context           $context           context
     * @param AvailableMethods  $availableMethods  available method
     * @param PaymentFeeFactory $paymentFeeFactory payment fee factory
     * @param CollectionFactory $collectionFactory collection factory
     */
    public function __construct(
        Context $context,
        AvailableMethods $availableMethods,
        PaymentFeeFactory $paymentFeeFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_paymentFeeFactory = $paymentFeeFactory;
        $this->_availableMethods = $availableMethods;
        parent::__construct($context);
    }

    /**
     * Update
     *
     * @return $this
     * @throws \Exception
     */
    public function execute()
    {
        $collection = $this->_collectionFactory->create();
        $model = $this->_paymentFeeFactory->create();
        $availableMethods = $this->_availableMethods->toOptionArray();
        $methods = array_keys($availableMethods);
        $count = 0;
        $oldMethods = [];

        foreach ($collection as $item) {
            $oldMethods[] = $item->getPaymentCode();
        }
        $newMethods = array_diff($methods, $oldMethods);

        if ($newMethods) {
            foreach ($newMethods as $code) {
                $model->setPaymentCode($code);
                $model->setPaymentName($availableMethods[$code]['label']);
                try {
                    $model->save();
                } catch (\Exception $e) {
                    throw $e;
                }
                //Clear Data session
                $model->setId(null);
                $count++;
            }
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been found.', $count));
        /**
         * Result redirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect redirect
         */

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');

    }

    /**
     * Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluecom_PaymentFee::index');
    }
}