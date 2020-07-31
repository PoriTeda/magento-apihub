<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class ConvertOrderItemToQuoteItem implements ObserverInterface
{
    protected $_salesAdminHelper;

    protected $_salesAddressHelper;

    protected $_adminQuoteSession;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;


    public function __construct(
        \Riki\Sales\Helper\Admin $salesAdminHelper,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Backend\Model\Session\Quote $adminQuoteSession,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ){
        $this->_salesAdminHelper = $salesAdminHelper;
        $this->_salesAddressHelper = $addressHelper;
        $this->_adminQuoteSession = $adminQuoteSession;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $orderItem = $observer->getOrderItem();
        $quoteItem = $observer->getQuoteItem();
        if ($orderItem->getData('is_riki_machine')) {
            $quoteItem->setData('is_riki_machine',1);
            $buyRequest = $orderItem->getBuyRequest();
            if (isset($buyRequest['options']) and isset($buyRequest['options']['free_machine_item'])) {
                $quoteItem->setOriginalCustomPrice(0);
            }
        }

        $productIdToAddressId = $this->_adminQuoteSession->getData('edit_order_products_address_ids');

        if(!is_array($productIdToAddressId))
            $productIdToAddressId = [];

        $customerAddressId = $this->_salesAddressHelper->getCustomerAddressIdByOrderItemId($orderItem->getId());

        if($this->_salesAddressHelper->isValidCustomerAddress($customerAddressId, $orderItem->getOrder()->getCustomerId())){
            $quoteItem->setAddressId($customerAddressId);

            $productIdToAddressId[] = [$orderItem->getProductId(), $customerAddressId];
        }

        $this->_adminQuoteSession->setData('edit_order_products_address_ids', $productIdToAddressId);

        if((int)$orderItem->getFreeOfCharge()){
            $this->setCustomPrice($quoteItem);
        }
    }

    /**
     * Prepares custom price and sets into a BuyRequest object as option of quote item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array
     */
    protected function setCustomPrice(\Magento\Quote\Model\Quote\Item $item)
    {
        $itemPrice = 0;
        /** @var \Magento\Framework\DataObject $infoBuyRequest */
        $infoBuyRequest = $item->getBuyRequest();
        if ($infoBuyRequest) {
            $infoBuyRequest->setCustomPrice($itemPrice);

            $infoBuyRequest->setValue($this->serializer->serialize($infoBuyRequest->getData()));
            $infoBuyRequest->setCode('info_buyRequest');
            $infoBuyRequest->setProduct($item->getProduct());

            $item->addOption($infoBuyRequest);
        }

        $item->setCustomPrice($itemPrice);
        $item->setOriginalCustomPrice($itemPrice);
    }
}