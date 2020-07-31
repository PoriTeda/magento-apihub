<?php

namespace Bluecom\Paygent\Block;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use \Riki\Sales\Helper\CheckRoleViewOnly;

class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    /**
     * @var CheckRoleViewOnly
     */
    protected $checkRoleOnly;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        CheckRoleViewOnly $checkRoleOnly,
        array $data = []
    )
    {
        $this->checkRoleOnly = $checkRoleOnly;
        parent::__construct( $context, $config, $data);
    }


    /**
     * @var string
     */
    protected $_template = 'Bluecom_Paygent::info/info.phtml';

    /**
     * Prepare payment information
     *
     * @param null $transport Transport
     *
     * @return $this|\Magento\Framework\DataObject
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        $info = $this->getInfo();

        if (!$this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::SHIPMENT_VIEW_ONLY)) {
            if ($url = $info->getPaygentUrl()) {
                $data[__('Payment URL')->getText()] = sprintf('<a href="%s" target="_blank">Go to payment page</a>', $url);
            }
        }

        if ($limitDate = $info->getPaygentLimitDate()) {
            $data[__('Payment limit date')->getText()] = preg_replace('/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/', '$1/$2/$3 $4:$5:$6', $limitDate);
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

    /**
     * Check order used ivr
     *
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkUseIvr()
    {
        return $this->getOrder()->getUseIvr();
    }

    /**
     * Get Ivr Transaction
     * 
     * @return mixed
     */
    public function getIvrTransaction()
    {
        $order = $this->getOrder();

        if ($order) {
            if ($order->getIvrTransaction() == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE) {
                return __('Canceled');
            } else if ($order->getIvrTransaction() == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::FAILED_RESPONSE_CODE) {
                return __('Get data from Ivr failed.');
            }
        }
        return $order->getIvrTransaction();
    }

    /**
     * Get Order
     * 
     * @return mixed
     * 
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrder()
    {
        return $this->getInfo()->getOrder();
    }

    /**
     * Get order id
     *
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getOrder()->getId();
    }

    /**
     * Check show buotton
     *
     * @return bool
     */
    public function checkShowButtonIvr(){
        return $this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::ORDER_VIEW_ONLY);
    }

    /**
     * can show transaction id for order detail
     *
     * @return bool
     */
    public function canShowTransactionId()
    {
        $ivrTransaction = $this->getOrder()->getIvrTransaction();

        if (!empty($ivrTransaction)
            && $ivrTransaction != \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::ERROR_CODE
        ) {
            return true;
        }

        return false;
    }

    /**
     * Can show get update button
     *
     * @return bool
     */
    public function canShowGetUpdateButton()
    {
        if ($this->checkShowButtonIvr()) {
            return false;
        }

        if (!$this->canShowTransactionId($this->getIvrTransaction())) {
            return false;
        }

        $order = $this->getOrder();

        $ivrTransaction = $order->getIvrTransaction();

        if ($ivrTransaction == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE
            || $ivrTransaction == \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::FAILED_RESPONSE_CODE
        ) {
            return false;
        }

        if ($order && $order->getStatus()
            && ($order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_CC
                || $order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED
            )
        ) {
            return true;
        }
        return false;
    }
}
