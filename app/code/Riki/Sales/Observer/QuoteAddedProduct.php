<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class QuoteAddedProduct implements ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_quoteSession;
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_salesHelper;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;

    public function __construct(
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Riki\Sales\Helper\Data $helper,
        \Magento\Framework\Webapi\Rest\Request $request
    ){
        $this->_quoteSession = $quoteSession;
        $this->_salesHelper = $helper;
        $this->request =$request;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $items = $observer->getItems();

        if(isset($items[0]) && $items[0] instanceof \Magento\Quote\Model\Quote\Item){
            $channel = $this->_salesHelper->getDistributionChanelByCustomerId($items[0]->getQuote()->getCustomerId());

            foreach($items as $item){
                $item->setDistributionChannel($channel);
            }
        }
    }
}