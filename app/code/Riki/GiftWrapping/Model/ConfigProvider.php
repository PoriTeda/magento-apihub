<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GiftWrapping\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftWrapping\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\Helper\Data as PricingData;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Model\Calculation;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ConfigProvider extends \Magento\GiftWrapping\Model\ConfigProvider
{
    /**
     * @var CartFactory
     */
    protected $checkoutCartFactory;

    /**
     * @var ProductRepositoryInterface
     * @deprecated 101.0.0
     */
    protected $productRepository;

    /**
     * Gift wrapping data
     *
     * @var Data
     */
    protected $giftWrappingData = null;

    /**
     * @var bool
     */
    protected $giftWrappingAvailable = false;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected $designCollection;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $wrappingCollectionFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PricingData
     */
    protected $pricingHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Tax class key factory
     *
     * @var TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyFactory;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $_taxCalculation;

    /**
     * Tax calculation tool
     *
     * @var Calculation
     */
    protected $calculationTool;

    /**
     * ConfigProvider constructor.
     * @param CartFactory $checkoutCartFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Data $giftWrappingData
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $wrappingCollectionFactory
     * @param UrlInterface $urlBuilder
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param CheckoutSession $checkoutSession
     * @param PricingData $pricingHelper
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param TaxClassKeyInterfaceFactory $taxClassKeyFactory
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     */
    public function __construct(
        CartFactory $checkoutCartFactory,
        ProductRepositoryInterface $productRepository,
        Data $giftWrappingData,
        StoreManagerInterface $storeManager,
        CollectionFactory $wrappingCollectionFactory,
        UrlInterface $urlBuilder,
        Repository $assetRepo,
        RequestInterface $request,
        LoggerInterface $logger,
        CheckoutSession $checkoutSession,
        PricingData $pricingHelper,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        Calculation $calculationTool
    ) {
        $this->_taxCalculation = $taxCalculation;
         $this->calculationTool = $calculationTool;
        parent::__construct(
            $checkoutCartFactory,
            $productRepository,
            $giftWrappingData,
            $storeManager,
            $wrappingCollectionFactory,
            $urlBuilder,
            $assetRepo,
            $request,
            $logger,
            $checkoutSession,
            $pricingHelper,
            $quoteIdMaskFactory,
            $taxClassKeyFactory
        );
    }

    public function getDesignsInfo()
    {
        $designInfo = [];
        /** @var $item \Magento\GiftWrapping\Model\Wrapping */
        foreach ($this->getDesignCollection()->getItems() as $item) {
            $design = [];
            foreach ($this->getQuote()->getAllShippingAddresses() as $address) {
                if ($this->getDisplayWrappingBothPrices()) {
                    $design['price_incl_tax'] = $this->calculatePrice(
                        $item,
                        $item->getBasePrice(),
                        $address,
                        true
                    );
                    $design['price_excl_tax'] = $this->calculatePrice($item, $item->getBasePrice(), $address);
                } else {
                    $design['price'] = $this->calculatePrice(
                        $item,
                        $item->getBasePrice(),
                        $address,
                        $this->getDisplayWrappingIncludeTaxPrice()
                    );
                }
                $design['path'] = $item->getImageUrl();
                $design['label'] = $item->getGiftName();
                $design['id'] = $item->getId();
            }
            $designInfo[$item->getId()] = $design;
        }

        return $designInfo;
    }

    /**
     * @return array
     */
    public function getDesignsInfoMinicCart()
    {
        $designInfo = [];
        /** @var $item \Magento\GiftWrapping\Model\Wrapping */
        foreach ($this->getDesignCollection()->getItems() as $item) {
            $design = [];
            $design['path'] = $item->getImageUrl();
            $design['label'] = $item->getGiftName();
            $design['id'] = $item->getId();
            $design['price'] =  $this->calTax($item->getBasePrice());
            $design['basePrice'] =  $item->getBasePrice();
            $designInfo[$item->getId()] = $design;
        }

        return $designInfo;
    }

    /**
     * @param $wrapping_fee
     * @return mixed
     */
    private function calTax($wrapping_fee)
    {
        $wrappingTax  = $this->giftWrappingData->getWrappingTaxClass();
        $wrappingRate = $this->_taxCalculation->getCalculatedRate($wrappingTax);
        if ($wrapping_fee > 0) {
            $taxRate = $wrappingRate / 100;
            $wrapping_fee = $wrapping_fee + ($taxRate * $wrapping_fee);
        }
        return  $this->calculationTool->round($wrapping_fee);
    }

}
