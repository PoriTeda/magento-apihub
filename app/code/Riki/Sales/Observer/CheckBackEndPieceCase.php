<?php

namespace Riki\Sales\Observer;

class CheckBackEndPieceCase implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * CheckBackEndPieceCase constructor.
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_sessionQuote = $sessionQuote;
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if('order_create' === $this->_request->getControllerName()) {

            $params = $this->_request->getParams();
            $aParamProducts = isset($params['item'])?$params['item']:array();
            if(count($aParamProducts)){

                //get current quote
                $itemsAvailable = $this->_sessionQuote->getQuote()->getAllVisibleItems();
                $itemsAvailableUnit = array();
                if(count($itemsAvailable)){
                    foreach($itemsAvailable as $itemAvailable){
                        if($itemAvailable->getItemId()){
                            $itemsAvailableUnit[$itemAvailable->getProductId()] = $itemAvailable->getUnitCase();
                        }
                    }
                }

                $data = $observer->getRequestModel()->getPost();

                $items = array();
                if(isset($data['item'])){
                    $items = (array)$data['item'];
                }

                foreach($items as $productId =>  &$item){
                    // check piece and case exist
                    if(array_key_exists($productId,$itemsAvailableUnit) && $itemsAvailableUnit[$productId] != null && isset($item['case_display']) && $itemsAvailableUnit[$productId] != strtoupper($item['case_display'])){
                        throw new \Magento\Framework\Exception\LocalizedException(__('You can\'t have a piece and a case of the same product in the shopping cart'));
                    }
                }
            }

        }
    }

}
