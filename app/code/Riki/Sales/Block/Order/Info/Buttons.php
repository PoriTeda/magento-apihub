<?php
namespace Riki\Sales\Block\Order\Info;
use Riki\Sales\Helper\Order;

class Buttons extends \Magento\Sales\Block\Order\Info\Buttons
{
    /**
     * @var string
     */
    protected $_template = 'order/info/buttons.phtml';

    protected $_orderHelper ;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Riki\Sales\Helper\Order $orderHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
       parent::__construct($context,$registry,$httpContext,$data);
       $this->_orderHelper = $orderHelper;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getSelectionTemplate(\Magento\Sales\Model\Order $order)
    {
        $issueNumber = $this->_orderHelper->getIssueReceipPrint($order);
        $isAmbassador = $this->_orderHelper->isCustomerMembershipAmbassador($order->getCustomerId());
        if($isAmbassador){
            $prefixAmbassador = 'am_';
        }else{
            $prefixAmbassador = '';
        }
        switch($issueNumber)
        {
            case 1 : //can not print receip invoice
                $template = 'not_shipped_print';
                break;
            case 2 : // can no print receip
                $template = 'can_not_print';
                break;
            case 3 : // able to print
                $template = 'print_order';
                break;
            case 4: //hidden
            default:
                $template = '';
                break;
        }
        $template = $template ? $prefixAmbassador.$template : '';
        return $template;
    }

    /**
     * @param $orderId
     * @return string
     */
    public function getPrintUrlAdmin($orderId){
        return $this->getUrl('riki_sales/order_index/printReceipt', ['order_id' =>  $orderId]);
    }
    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getReceiptNamePrint()
    {

        return $this->_orderHelper->getReceiptNamePrint($this->getOrder());
    }
    public function getReceiptNamePrintAdmin($order)
    {

        return $this->_orderHelper->getReceiptNamePrint($order);
    }
}