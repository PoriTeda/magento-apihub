<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class QuoteAddedItem implements ObserverInterface
{

    /** @var \Magento\Framework\App\State  */
    protected $appState;

    /** @var \Magento\Framework\Webapi\Rest\Request  */
    protected $restRequest;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * QuoteAddedItem constructor.
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\Registry $registry
    ){
        $this->appState = $appState;
        $this->restRequest = $request;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getQuoteItem();

        $product = $quoteItem->getProduct();

        $bookingType = 0;


        $isImportMultipleOrderCsv = $quoteItem->getQuote()->getData('is_csv_import_order_flag');

        if(
            $this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE || ($isImportMultipleOrderCsv==true)
        ){
            $quote = $quoteItem->getQuote();

            if ($quote->getData('is_generate')) {
                $bookingType = 1;
            } else {

                $chargeType = $quote->getData('charge_type');

                switch($chargeType){
                    case \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL:
                        $bookingType = 1;
                        break;
                    case \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_REPLACEMENT:
                        $bookingType = 2;
                        break;
                    case \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_FREE_SAMPLE:
                        $bookingType = 3;
                        break;
                }
            }
        } else {
            $param = $this->restRequest->getParams();
            if (!isset($param['call_machine_api'])) {
                $bookingType = 1;
            }
        }

        switch ($bookingType) {
            case 1:
                $quoteItem->setBookingWbs($product->getBookingItemWbs());
                $quoteItem->setBookingAccount($product->getBookingItemAccount());
                $quoteItem->setBookingCenter($product->getBookingProfitCenter());
                break;
            case 2:
                $quoteItem->setBookingAccount($product->getBookingMachineMtAccount());
                $quoteItem->setBookingCenter($product->getBookingMachineMtCenter());
                break;
        }
    }
}