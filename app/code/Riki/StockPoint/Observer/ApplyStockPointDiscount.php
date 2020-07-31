<?php
namespace Riki\StockPoint\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Riki\Subscription\Helper\Order\Data as SubscriptionHelperData;
use Riki\Subscription\Model\ProductCart\ProductCart;

class ApplyStockPointDiscount implements ObserverInterface
{

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    private $buildStockPointPostData;

    private $loadedDiscountProfiles = [];

    /**
     * ApplyStockPointDiscount constructor.
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     */
    public function __construct(
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
    ) {
        $this->buildStockPointPostData = $buildStockPointPostData;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        if (($profileId = $product->getData(SubscriptionHelperData::SUBSCRIPTION_PROFILE_ID_FIELD_NAME)) &&
            $product->getData(SubscriptionHelperData::IS_STOCK_POINT_PROFILE)
        ) {
            $discountRate = $this->getStockPointDiscountRate($product);

            $discountAmount = $product->getData('final_price') * ((int)$discountRate / 100);

            $discountAmount = floor($discountAmount);

            $finalPrice = $product->getData('final_price') - $discountAmount;
            $product->setFinalPrice($finalPrice);
            $product->setData('stock_point_applied_discount_amount', $discountAmount);
        }

        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return int|mixed
     */
    protected function getStockPointDiscountRate(\Magento\Catalog\Model\Product $product)
    {
        if ($product->getData(
            ProductCart::IS_SIMULATOR_PROFILE_ITEM_KEY
        )
        ) {
            $discountRate = $product->getData(
                ProductCart::PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY
            );
        } else {
            // only call API when quote item was set full data
            if ($product->getData(
                ProductCart::IS_READY_TO_CALL_DISCOUNT_API_KEY
            )
            ) {
                $discountRate = $this->getStockPointDiscountRateFromApi(
                    $profileId = $product->getData(SubscriptionHelperData::SUBSCRIPTION_PROFILE_ID_FIELD_NAME),
                    $product->getData('delivery_date')
                );
                $product->setData('stock_point_applied_discount_rate', $discountRate);
            } else {
                $discountRate = 0;
            }
        }

        return $discountRate;
    }

    /**
     * @param $profileId
     * @param $deliveryDate
     * @return mixed
     */
    private function getStockPointDiscountRateFromApi($profileId, $deliveryDate)
    {
        $key = $profileId . '-' . strtotime($deliveryDate);
        if (!isset($this->loadedDiscountProfiles[$key])) {
            $this->loadedDiscountProfiles[$key] = $this->buildStockPointPostData->getDiscountRate(
                $profileId,
                $deliveryDate
            );
        }

        return $this->loadedDiscountProfiles[$key];
    }
}
