<?php

namespace Riki\GiftWrapping\Block\Adminhtml\Order\Create;

use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;

class Items extends \Magento\GiftWrapping\Block\Adminhtml\Order\Create\Items
{
    /**
     * Tax class key factory
     *
     * @var TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        array $data = []
    ) {
        $this->taxClassKeyFactory = $taxClassKeyFactory;
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $giftWrappingData, $wrappingCollectionFactory, $data);
    }

    
    /**
     * Prepare and return quote items info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getItemsInfo()
    {

        $data = [];
        foreach ($this->getQuote()->getAllItems() as $key => $item) {
            if ($item->getParentItem()) {
                continue;
            }

            if ($this->getDisplayGiftWrappingForItem($item)) {
                $temp = [];
                if ($price = $item->getProduct()->getGiftWrappingPrice()) {
                    if ($this->getDisplayWrappingBothPrices()) {
                        $temp['price_incl_tax'] = $this->calculatePrice(
                            new \Magento\Framework\DataObject(),
                            $price,
                            true
                        );
                        $temp['price_excl_tax'] = $this->calculatePrice(new \Magento\Framework\DataObject(), $price);
                    } else {
                        $temp['price'] = $this->calculatePrice(
                            new \Magento\Framework\DataObject(),
                            $price,
                            $this->getDisplayWrappingPriceInclTax()
                        );
                    }
                }

                if($item->getProduct()->getGiftWrapping()){
                    $temp['listgift'] = $item->getProduct()->getGiftWrapping();
                }else{
                    $temp['listgift'] = '';
                }
                $temp['design'] = $item->getGwId();
                $data[$item->getId()] = $temp;
            }
        }

        return new \Magento\Framework\DataObject($data);
    }



    /**
     * Calculate price
     *
     * @param \Magento\Framework\DataObject $item
     * @param float $basePrice
     * @param bool $includeTax
     * @return string
     */
    public function calculatePrice($item, $basePrice, $includeTax = false)
    {
        $shippingAddress = $this->getQuote()->getShippingAddress();
        $billingAddress = $this->getQuote()->getBillingAddress();

        $taxClassId = $this->_giftWrappingData->getWrappingTaxClass($this->getStoreId());

        $taxClassKey = $this->taxClassKeyFactory->create();
        $taxClassKey->setType(TaxClassKeyInterface::TYPE_ID);
        $taxClassKey->setValue($taxClassId);

        $item->setTaxClassKey($taxClassKey);

        $price = $this->_giftWrappingData->getPrice($item, $basePrice, $includeTax, $shippingAddress, $billingAddress);

        return $this->priceCurrency->convertAndFormat($price, false);
    }
    public function getDisplayGiftWrappingForItem($item)
    {
        $allowed = $item->getProduct()->getGiftWrappingAvailable();
        $giftProduct = 0 ;
        if($item->getProduct()->getGiftWrapping()){
            $giftProduct = 1 ;
        }
        return $this->_giftWrappingData->isGiftWrappingAvailableForProduct($allowed, $this->getStoreId()) ? $giftProduct :$this->_giftWrappingData->isGiftWrappingAvailableForProduct($allowed, $this->getStoreId()) ;
    }

}
