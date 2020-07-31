<?php
namespace Riki\Checkout\Helper;

use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /* @var \Riki\SubscriptionPage\Helper\Data */
    protected $subPageHelperData;

    public function __construct(
        \Riki\SubscriptionPage\Helper\Data $subPageHelperData,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->subPageHelperData = $subPageHelperData;
        parent::__construct($context);
    }

    /**
     * Get arr product for subscription hanpukai
     *
     * @param $courseId
     *
     * @return array
     */

    public function getArrProductFirstDeliveryHanpukai($courseId)
    {
        $arrProduct = array();
        $subModel = $this->subPageHelperData->getSubscriptionCourseModelFromCourseId($courseId);
        if ($subModel->getData('hanpukai_type') == CourseType::TYPE_HANPUKAI_FIXED) {
            $arrProduct = $this->subPageHelperData->getArrProductFixedHanpukai($courseId);
        }

        if ($subModel->getData('hanpukai_type') == CourseType::TYPE_HANPUKAI_SEQUENCE) {
            $arrProduct = $this->subPageHelperData->getArrProductForFirstDeliveryHanpukaiSequence($courseId);
        }
        return $arrProduct;
    }

    /**
     * @param $originData
     * @param $cartData
     *
     * @return int|bool
     */
    public function calculateFactor($originData, $cartData, $quote)
    {
        if (count($originData) == 0) {
            // admin delete all product config
            return false;
        }

        $arrMapItemIdToProductId = $this->mapQuoteItemIdToProductId($quote);
        $factor = 1;
        $count = 1;
        foreach($cartData as $index => $data) {
            // index is quote item id
            $productId = $arrMapItemIdToProductId[$index];
            if (in_array($productId, array_keys($originData))) {
                if ($data['qty'] % $originData[$productId] != 0) {
                    // admin change config or unknown error
                    return false;
                } else {
                    if ($count == 1) {
                        $factor = (int)$data['qty'] / (int)$originData[$productId]['qty'];
                    } else {
                        $tmp = (int)$data['qty'] / (int)$originData[$productId]['qty'];
                        // all factor is same if not some thing wrong
                        if ($factor != $tmp) {
                            return false;
                        }
                    }
                }
                $count ++;
            }

        }
        return $factor;
    }

    /**
     * Map quote item id to product id
     *
     * @param $quote
     *
     * @return array
     */
    public function mapQuoteItemIdToProductId($quote)
    {
        $resultArr = array();
        /* @var $quote \Magento\Quote\Model\Quote */
        foreach ($quote->getAllItems() as $item) {
            $resultArr[$item->getData('item_id')] = $item->getData('product_id');
        }
        return $resultArr;
    }

    /**
     * Make cart data fro quote
     *
     * @param $quote
     * @return array
     */
    public function makeCartDataFromQuote($quote)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        $cartData = array();
        foreach ($quote->getAllItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            $cartData[$item->getData('item_id')]['qty'] = $item->getData('qty');
        }
        return $cartData;
    }
}