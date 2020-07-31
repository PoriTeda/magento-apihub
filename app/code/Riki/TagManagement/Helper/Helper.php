<?php

namespace Riki\TagManagement\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Helper extends AbstractHelper
{
    //these are product item SKU of current KSS system.
    const SKU_DEFINED_1 = [
        '0012137192',
        '0012137193',
        '0012156110',
        '0012153786',
        '0012184837',
        '0012184836',
        '0012182020',
        '0012172399',
        '0012172398',
        '0012168460'
    ];
    const SKU_DEFINED_2 = [
        '0012227932',
        '0012227933',
        '0012183703',
        '0012205414',
        '0012227932',
        '0012227933',
        '0012183703',
        '0012205414',
    ];

    const SKU_DEFINED_3 = [
        '0012226278',
        '0012226277',
        '0012226270',
        '0012226279',
    ];

    const SKU_DEFINED_4 = [
        '0012306396',
        '00123063970',
    ];
    //All page
    const CONFIG_TAG_YAHOO = 'setting_tag/group_tag/script_manager_yahoo';
    const CONFIG_TAG_AFFILIATE_GIFT_ORDER_COMPLETE_EC =
        'setting_tag/group_order_complete/script_manager_affiliategiftordercompleteec';
    const CONFIG_TAG_AFFILIATE_ORDER_COMPLETE_EC =
        'setting_tag/group_order_complete/script_manager_affiliateordercompleteec';
    const CONFIG_TAG_AFFILIATE_AFFILIATE_ORDER_COMPLETE_TAG =
        'setting_tag/group_order_complete/script_manager_affiliateordercompletetag';
    const CONFIG_TAG_AFFILIATE_ORDER_COMPLETE_BENEPOSITE =
        'setting_tag/group_order_complete/script_manager_affiliateordercompletebeneposite';
    const CONFIG_TAG_AFFILIATE_ORDER_COMPLETE_BENEFITONE =
        'setting_tag/group_order_complete/script_manager_affiliateordercompletebenefitone';
    const CONFIG_TAG_LINE_TAG =
        'setting_tag/group_order_complete/script_manager_line_tag';
    const CONFIG_TAG_PRODUCT_DETAIL =
        'setting_tag/group_product_detail/script_manager_product_detail';
    protected $profileFactory;
    protected $courseFactory;
    protected $collectionProfile;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->profileFactory = $profileFactory;
        $this->courseFactory = $courseFactory;
        $this->collectionProfile = $collectionFactory;
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    public function getConfigYahoo()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_TAG_YAHOO, $storeScope);
    }
    public function getConfigTagOrderComplete($order)
    {
        $script = '';
        $script .=  $this->getConfigAffiliateOrderCompleteTag($order);
        $script .=  $this->getConfigAffiliateOrderCompleteEC();
        $script .=  $this->getConfigAffiliateOrderCompleteBeneposite($order);
        $script .=  $this->getConfigAffiliateOrderCompleteBenefitOne($order);
        return $script;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */

    public function displayScriptGMONDGNBA(\Magento\Sales\Model\Order $order, $courseId = null)
    {
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $products[] = $item->getSku(). '_' . $courseId;
        }
        $script = '<img class="no-display" src="https://ad.atown.jp/adserver/ap?aid=2265&u1='
            .$order->getIncrementId().'&u2='.implode(',', $products).'"width="1" height="1">';
        return $script;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function displayScriptA8(\Magento\Sales\Model\Order $order, $courseId = null)
    {
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $products[] = 'si='.(int)($item->getPrice()).'.'.
                (int)($item->getQtyOrdered()).'.'.
                $item->getPrice() *(int)($item->getQtyOrdered()).'.'.$item->getSku().'_'.$courseId;
        }
        $script = '<img class="no-display" src="https://px.a8.net/cgi-bin/a8fly/sales?pid=s00000014673007&so='.
            $order->getIncrementId().'&'.implode('&', $products).'" width="1" height="1">';
        return $script;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $courseId
     * @return string
     */

    public function formatGMOSPTScript(\Magento\Sales\Model\Order $order, $courseId = null)
    {
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $products[] = $item->getSku() . '_' . $courseId;
        }
        $script = '<img class="no-display" src="https://ad.atown.jp/adserver/ap?aid=3905&u1='.
            $order->getIncrementId().
            '&u2='.implode(',', $products).
            '"width="1" height="1">';
        return $script;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $script
     * @return mixed
     */

    public function displayScriptA8SPT(\Magento\Sales\Model\Order $order, $courseId = null)
    {
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $products[] = 'si=1.1.1.'.$item->getSku().'_'.$courseId;
        }
        $script = '<img class="no-display" src="https://px.a8.net/cgi-bin/a8fly/sales?pid=s00000014673004&so='.
            $order->getIncrementId().'&'.
            implode('&', $products).'" width="1" height="1"> ';
        return $script;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $script
     * @return mixed
     */
    public function displayScriptAT(\Magento\Sales\Model\Order $order, $courseId = null)
    {
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $products[] = 'vi='.$item->getSku().'_'.
                $courseId.'.'.(int)($item->getQtyOrdered()).'.'.(int)($item->getPriceInclTax());
        }
        $script = '<img class="no-display" src="https://is.accesstrade.net/cgi-bin/isatV2/nestle3/isatWeaselV2.cgi?result_id=3&verify='.
            $order->getIncrementId().'&'.implode('&', $products).'" width="1" height="1">';
        return $script;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $script
     * @return mixed
     */
    public function displayScriptA8NDG(\Magento\Sales\Model\Order $order, $courseId = null)
    {
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $products[] = 'si=1.1.1.'.$item->getSku().'_'.$courseId;
        }
        $script = '<img class="no-display" src="https://px.a8.net/cgi-bin/a8fly/sales?pid=s00000014673002&so='.
            $order->getIncrementId().'&'.implode('&', $products).'" width="1" height="1">';
        return $script;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $storeScope
     * @return string
     */
    public function getConfigScriptSubscriptionCodeRT000033S(\Magento\Sales\Model\Order $order, $courseId)
    {
        $productId = [];
        $html = '';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $productId[] = $item->getProductId() . '_' . $courseId;
        }
        $html .= str_replace(
            [
                '[order number]',
            ],
            [
                $order->getIncrementId()
            ],
            $this->scopeConfig->getValue('setting_tag/group_order_complete/line_tag_nba', $storeScope)
        );
        return $html;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $storeScope
     * @return string
     */
    public function getConfigScriptSubscriptionCodeRT000020S(\Magento\Sales\Model\Order $order)
    {
        $html = '';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $date = $this->timezone->formatDateTime(
            $order->getCreatedAt(),
            $dateType = \IntlDateFormatter::SHORT,
            $timeType = \IntlDateFormatter::SHORT,
            $locale = null,
            $timezone = null,
            'yyyyMMddHHmmss'
        );
        $orderId = $order->getIncrementId().'_' . $date;

        $html .= str_replace(
            '[order number]',
            $orderId,
            $this->scopeConfig->getValue(
                'setting_tag/group_order_complete/ca_reward_nba',
                $storeScope
            )
        );
        return $html;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $courseId
     * @return string
     */
    public function getConfigScriptSubscriptionCodeRT000032S(\Magento\Sales\Model\Order $order, $courseId)
    {
        $html = '';
        $productId = [];
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $productId[] = $item->getProductId() . '_' . $courseId;
        }
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $html .= str_replace(
            [
                '[order number]',
            ],
            [
                $order->getIncrementId()
            ],
            $this->scopeConfig->getValue('setting_tag/group_order_complete/line_tag_ndg', $storeScope)
        );
        return $html;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $storeScope
     * @return string
     */
    public function getConfigScriptSubscriptionCodeRT000019S(\Magento\Sales\Model\Order $order)
    {
        $html = '';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $date = $this->timezone->formatDateTime(
            $order->getCreatedAt(),
            $dateType = \IntlDateFormatter::SHORT,
            $timeType = \IntlDateFormatter::SHORT,
            $locale = null,
            $timezone = null,
            'yyyyMMddHHmmss'
        );
        $orderId = $order->getIncrementId().'_' . $date;
        $html .= str_replace(
            '[order number]',
            $orderId,
            $this->scopeConfig->getValue(
                'setting_tag/group_order_complete/ca_reward_ndg',
                $storeScope
            )
        );
        return $html;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $storeScope
     * @return string
     */
    public function getConfigScriptSubscriptionCodeRT000034S(\Magento\Sales\Model\Order $order, $courseId)
    {
        $html = '';
        $productId = [];
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $productId[] = $item->getProductId() . '_' . $courseId;
        }
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $html .= str_replace([
            '[order number]',
        ], [
            $order->getIncrementId()
        ], $this->scopeConfig->getValue('setting_tag/group_order_complete/line_tag_spt', $storeScope));
        return $html;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $storeScope
     * @return string
     */
    public function getConfigScriptSubscriptionCodeRT000002S(\Magento\Sales\Model\Order $order)
    {
        $html = '';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $date = $this->timezone->formatDateTime(
            $order->getCreatedAt(),
            $dateType = \IntlDateFormatter::SHORT,
            $timeType = \IntlDateFormatter::SHORT,
            $locale = null,
            $timezone = null,
            'yyyyMMddHHmmss'
        );
        $orderId = $order->getIncrementId().'_' . $date;
        $html .= str_replace(
            '[order number]',
            $orderId,
            $this->scopeConfig->getValue(
                'setting_tag/group_order_complete/ca_reward_spt',
                $storeScope
            )
        );
        return $html;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @return script
     */
    public function getConfigAffiliateGiftOrderCompleteEC(\Magento\Sales\Model\Order $order)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $script =  $this->scopeConfig->getValue(self::CONFIG_TAG_AFFILIATE_GIFT_ORDER_COMPLETE_EC, $storeScope);
        $script = str_replace('<%= transactionIds %>', $order->getIncrementId(), $script);
        $script = str_replace('<%= transactionTotal %>', $order->getBaseGrandTotal(), $script);
        $script = str_replace('<%= transactionTax %>', $order->getTaxAmount(), $script);
        $script = str_replace(
            '<%= transactionShipping %>',
            $order->getShippingInclTax() +
            $order->getData('fee'),
            $script
        );
        $replaceItems = '';
        $totalItemCount = $order->getTotalItemCount();
        $i = 1;
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $replaceItems .= "{
                                    'name': '".$item->getName()."',
                                    'category': '',
                                    'price': '".$item->getPriceInclTax()."',
                                    'quantity': '".(int)($item->getQtyOrdered())."'
                              }".(($totalItemCount != $i) ? ',' : '')."";
            $i++;
        }
        $script = str_replace('<%= items %>', $replaceItems, $script);
        return $script;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getConfigAffiliateOrderCompleteTag(\Magento\Sales\Model\Order $order)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $script =  $this->scopeConfig->getValue(self::CONFIG_TAG_AFFILIATE_AFFILIATE_ORDER_COMPLETE_TAG, $storeScope);
        $script = str_replace('<%= obj.getOrderNo() %>', $order->getIncrementId(), $script);
        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        foreach ($order->getAllItems() as $item) {
            $skus[] = $item->getSku();
        }
        $script = str_replace('<%=skuParam %>', implode('|', $skus), $script);
        $script = str_replace(
            '<%=purchasingAmountParam%>',
            (int)($order->getTotalQtyOrdered()),
            $script
        );
        $script = str_replace('<%=priceParam%>', $order->getBaseGrandTotal(), $script);

        $script = str_replace('<%=CampaignIdParam%>', $order->getCampaignId(), $script);

        return $script;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getConfigAffiliateOrderCompleteEC()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $script =  $this->scopeConfig->getValue(self::CONFIG_TAG_AFFILIATE_ORDER_COMPLETE_EC, $storeScope);
        return $script;
    }

    /**
     * @return mixed
     */

    public function getConfigTagProductDetail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_TAG_PRODUCT_DETAIL, $storeScope);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed|string
     */
    public function getConfigTagLineTag(\Magento\Sales\Model\Order $order)
    {
        $script = '';
        if ($order->hasData('riki_type')) {
            if ($order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION ||
                $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT
            ) {
                $profileId = $order->getData('subscription_profile_id');
                /**
                 * @var \Riki\Subscription\Model\Profile\Profile $profile
                 */
                $profile = $this->profileFactory->create()->load($profileId);
                if ($profile->getId()) {
                    $courseId = $profile->getCourseId();
                    $course = $this->courseFactory->create()->load($courseId);
                    if ($course->getId()) {
                        if ($course->getData('course_code') == 'RT000001S') {
                            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
                            $script =  $this->scopeConfig->getValue(self::CONFIG_TAG_LINE_TAG, $storeScope);
                        }
                    }
                }
            }
        }
        return $script;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $subscriptionCode
     * @return bool
     */
    public function checkProductBelongSubsciprtionByFixedCode($product, $subscriptionCode)
    {
        /**
         *@var \Riki\SubscriptionCourse\Model\ResourceModel\Course\Collection $collection
         */
        $collection = $this->collectionProfile->create();
        $collection = $collection
            ->addFieldToSelect(['course_id', 'course_code', 'subscription_type'])
            ->addFieldToFilter('course_code', $subscriptionCode)
            ->setPageSize(1);
        if ($collection->getSize()) {
            /**
             * @var \Riki\Subscription\Model\Profile\Profile $subscription
             */
            $subscription = $collection->getFirstItem();
            $courseId = $subscription->getCourseId();
            if ($subscription->getData('subscription_type') ==
                \Riki\SubscriptionCourse\Model\Course\Type::DEFAULT_TYPE) {
                //get categorie assign product
                $categories = $product->getCategoryIds($courseId);
                //categories of subscription
                $cateSubscription = $this->courseFactory->create()->getResource()->getCategoryIds($courseId);
                if (array_intersect($categories, $cateSubscription)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed|string
     */
    public function getConfigAffiliateOrderCompleteBeneposite(\Magento\Sales\Model\Order $order)
    {
        $script = '';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;

        /**
         * @var \Magento\Sales\Model\Order\Item $item
         */
        $tid = 401;
        foreach ($order->getAllItems() as $item) {
            $sku = trim($item->getSku());
            if (in_array(trim($sku), self::SKU_DEFINED_2)) {
                $scriptRepair = $this->scopeConfig->getValue(
                    self::CONFIG_TAG_AFFILIATE_ORDER_COMPLETE_BENEPOSITE,
                    $storeScope
                );
                $scriptRepair = str_replace('<%= tid %>', $tid, $scriptRepair);
                $scriptRepair = str_replace('<%= obj.getOrderNo() %>', $order->getIncrementId(), $scriptRepair);
                $scriptRepair = str_replace('<%= quantity %>', (int)($item->getQtyOrdered()), $scriptRepair);
                $scriptRepair = str_replace('<%= amount %>', $item->getBaseRowTotalInclTax(), $scriptRepair);
                $script .= $scriptRepair;
                $tid++;
            }
        }
        return $script;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed|string
     */
    public function getConfigAffiliateOrderCompleteBenefitOne(\Magento\Sales\Model\Order $order)
    {
        $script = [];
        $storeScope   = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $scriptConfig = $this->scopeConfig->getValue(self::CONFIG_TAG_AFFILIATE_ORDER_COMPLETE_BENEFITONE, $storeScope);
        foreach ($order->getAllItems() as $item) {
            $sku = $item->getSku();
            if (in_array($sku, self::SKU_DEFINED_3) || in_array($sku, self::SKU_DEFINED_4)) {
                $tid = 621;
                if (in_array($sku, self::SKU_DEFINED_4)) {
                    $tid = 601;
                }
                $scriptTmp  = $scriptConfig;
                $scriptTmp = str_replace('<%= strTid.get(j) %>', $tid, $scriptTmp);
                $scriptTmp = str_replace('<%= obj.getOrderNo() %>', $order->getIncrementId(), $scriptTmp);
                $scriptTmp = str_replace('<%= quantity.get(j) %>', (int)($item->getQtyOrdered()), $scriptTmp);
                $scriptTmp = str_replace('<%= amount.get(j) %>', $item->getBaseRowTotalInclTax(), $scriptTmp);
                $script[] = $scriptTmp;
            }
        }

        return implode('', $script);
    }
}
