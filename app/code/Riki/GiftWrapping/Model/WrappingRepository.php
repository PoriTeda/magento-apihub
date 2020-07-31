<?php

namespace Riki\GiftWrapping\Model;

class WrappingRepository implements \Riki\GiftWrapping\Api\WrappingRepositoryInterface
{
    const RESPONSE_CODE        = 200;
    const MESSAGE_TYPE_SUCCESS = 'success';

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $wrappingCollectionFactory;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $helperWrapping;

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $calculationTool;

    /**
     * WrappingRepository constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface                           $storeManager
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param \Magento\Tax\Model\TaxCalculation                                    $taxCalculation
     * @param \Magento\GiftWrapping\Helper\Data                                    $helperWrapping
     * @param \Magento\Tax\Model\Calculation                                       $calculationTool
     */
    public function __construct(

        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\GiftWrapping\Helper\Data $helperWrapping,
        \Magento\Tax\Model\Calculation $calculationTool
    ) {
        $this->storeManager              = $storeManager;
        $this->wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->taxCalculation            = $taxCalculation;
        $this->helperWrapping            = $helperWrapping;
        $this->calculationTool           = $calculationTool;
    }

    /**
     * Response API format
     *
     * @param             $message
     * @param string|null $redirect
     * @param string|null $content
     *
     * @return mixed
     */
    protected function responseData($message, $redirect = null, $content = null)
    {
        $response['response'] = [
            "code"     => self::RESPONSE_CODE,
            "message"  => $message,
            "redirect" => $redirect,
            "content"  => $content
        ];
        return $response;
    }

    /**
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList()
    {
        $store = $this->storeManager->getStore();

        $giftWrappingCollection = $this->wrappingCollectionFactory->create();
        $giftWrappingCollection->addStoreAttributesToResult($store->getId());
        $giftWrappingCollection->applyStatusFilter();
        $giftWrappingCollection->applyWebsiteFilter($store->getWebsiteId());

        if (count($giftWrappingCollection)) {
            $wrappingTax  = $this->helperWrapping->getWrappingTaxClass($store);
            $wrappingRate = $this->taxCalculation->getCalculatedRate($wrappingTax);

            $giftWrappingData    = $giftWrappingCollection->getData();
            $giftWrappingInclTax = [];
            foreach ($giftWrappingData as $wrapping) {
                $wrapping_fee               = $wrapping['base_price'];
                $taxRate                    = $wrappingRate / 100;
                $wrapping['price_incl_tax'] = $this->calculationTool->round($wrapping_fee + ($taxRate * $wrapping_fee));

                $giftWrappingInclTax[] = $wrapping;
            }

            $message['type'] = self::MESSAGE_TYPE_SUCCESS;
            $message['text'] = __('Get list Gift Wrapping success');
            if($this->getDisplayWrappingPriceInclTax($store)){
                return $this->responseData([$message], null, $giftWrappingInclTax);
            }
            return $this->responseData([$message], null, $giftWrappingData);
        }
    }

    /**
     * Check ability to display prices including tax for gift wrapping in shopping cart
     *
     * @param $store
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @codeCoverageIgnore
     */
    public function getDisplayWrappingPriceInclTax($store)
    {
        return $this->helperWrapping->displayCartWrappingIncludeTaxPrice($store->getId());
    }
}
