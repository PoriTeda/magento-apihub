<?php
namespace Riki\Sales\Block\Adminhtml\Shipping;
use Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr;

/**
 * Class Form
 * @package Riki\Sales\Block\Adminhtml\Shipping
 */
class Form extends \Magento\Shipping\Block\Adminhtml\View\Form
{
    public function getIVRMessage(\Magento\Sales\Model\Order $order)
    {
        $message = '';
        $currencyCode = $order->getOrderCurrencyCode();
        $ivrTransaction = $order->getIvrTransaction();
        $ivrErrorStatus= [
            \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::CANCELED_CODE,
            \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::FAILED_RESPONSE_CODE,
            \Bluecom\Paygent\Controller\Adminhtml\Paygent\Ivr::ERROR_CODE
        ];
        if($order->getUseIvr()) // IVR order
        {
            if($ivrTransaction)
            {
                if(in_array($ivrTransaction,$ivrErrorStatus))
                {
                    switch($ivrTransaction)
                    {
                        case Ivr::CANCELED_CODE:
                            //IVR canceled
                            $message = __('Payment IVR canceled');
                            break;
                        case Ivr::FAILED_RESPONSE_CODE:
                            //IVR failed
                            $message = __('Payment IVR failed');
                            break;
                        case Ivr::ERROR_CODE:
                            //IVR failed
                            $message = __('Payment IVR failed');
                            break;
                    }
                }
                else
                {
                    //IVR Transaction success
                    $message = __('Payment IVR success');
                }
            }
        }
        else
        {
            $message = __('The order was placed using %1.', $currencyCode);
        }
        return $message;
    }
}