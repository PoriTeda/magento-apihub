<?php

namespace Riki\Subscription\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Setup\Exception;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;
use Zend\Http\Header\Exception\ExceptionInterface;

class EditSubscriptionOrderAfterSubmitAll implements ObserverInterface
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;

    /**
     * UpdateDataAfterEditOrder constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData
    ){
        $this->profileFactory = $profileFactory;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->helperProfileData = $helperProfileData;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order ){
            return;
        }
        $oldOrderId = $order->getRelationParentId();
        $subscriptionOrderTimes = null;
        $subscriptionCourse = null;
        $subscriptionFrequency = null;
        if($oldOrderId) {
            $subscriptionProfileId = $order->getData('subscription_profile_id');
            if($subscriptionProfileId) {
                try {
                    $this->updateProfileInfo($subscriptionProfileId,$order);
                }catch (\Exception $exception) {
                    $this->helperProfileData->getLogger()->critical($exception);
                }
            }
        }
    }

    /**
     * Update Profile sales_count and sales_value_count
     *
     * @param $subscriptionProfileId
     * @param \Magento\Sales\Model\Order $newOrder
     * @throws \Exception
     */
    private function updateProfileInfo($subscriptionProfileId,\Magento\Sales\Model\Order $newOrder) {
        $profileModel = $this->profileFactory->create()->load($subscriptionProfileId,null,true);
        if($profileModel->getId()) {
            $salesCount = $profileModel->getData('sales_count');
            $salesValueCount = $profileModel->getData('sales_value_count');
            $newSalesCount = 0;

            foreach ($newOrder->getAllItems() as $newItem) {
                if($newItem->getProductType() ==  \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE){
                    continue;
                }
                $buyRequest = $newItem->getBuyRequest();

                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }
                if ($newItem->getData('prize_id')) {
                    continue;
                }
                if($newItem->getData('is_riki_machine') and $newItem->getData('price') == 0){
                    continue;
                }
                $newSalesCount += $newItem->getQtyOrdered();
            }
            $newSalesValueCount = $newOrder->getGrandTotal();
            $salesCount = $salesCount + $newSalesCount;
            $salesValueCount = $salesValueCount + $newSalesValueCount;

            $profileModel->setData('sales_count',$salesCount);
            $profileModel->setData('sales_value_count',$salesValueCount);
            try{
                $profileModel->save();
            }catch (\Exception $e) {
                throw $e;
            }
            if($versionId = $this->helperProfileData->checkProfileHaveVersion($subscriptionProfileId)) {
                $versionProfileModel = $this->profileFactory->create()->load($versionId);
                if($versionProfileModel->getId()) {
                    $versionProfileModel->setData('sales_count',$salesCount);
                    $versionProfileModel->setData('sales_value_count',$salesValueCount);
                    try{
                        $versionProfileModel->save();
                    }catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
            if ($tmp = $this->helperProfileData->getTmpProfile($subscriptionProfileId)) {
                $tmpId = $tmp->getData('linked_profile_id');
                $tmpProfileModel = $this->profileFactory->create()->load($tmpId);
                if($tmpProfileModel->getId()) {
                    $tmpProfileModel->setData('sales_count',$salesCount);
                    $tmpProfileModel->setData('sales_value_count',$salesValueCount);
                    try{
                        $tmpProfileModel->save();
                    }catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
        }
    }

}
