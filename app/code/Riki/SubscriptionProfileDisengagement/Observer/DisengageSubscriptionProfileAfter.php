<?php

namespace Riki\SubscriptionProfileDisengagement\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class DisengageSubscriptionProfileAfter implements ObserverInterface
{
    protected $_subscriptionProfileData;

    protected $_subscriptionProfileCollection;

    protected $_rikiCustomerRepository;

    protected $_logger;

    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $subscriptionProfileData,
        \Riki\Subscription\Logger\Logger $logger,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Riki\Customer\Model\CustomerRepository $customerRepository
    ){
        $this->_subscriptionProfileData = $subscriptionProfileData;
        $this->_rikiCustomerRepository = $customerRepository;
        $this->_logger = $logger;
        $this->_subscriptionProfileCollection = $profileCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $observer->getProfile();

        $this->updateToConsumerDb($profile);

    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @return $this
     */
    protected function processOrders(\Riki\Subscription\Model\Profile\Profile $profile){
        $orders = $profile->getOrders();

        /** @var \Magento\Sales\Model\Order $order */
        foreach($orders as $order){

            $wasExported = false;

            if($order->hasShipments()){
                foreach($order->getShipmentsCollection() as $shipment){
                    if($shipment->getIsExported()){
                        $wasExported = true;
                        break;
                    }
                }
            }

            if(!$wasExported){
                try{
                    $order->setIsCancelByEditAction(true);
                    $order->cancel()
                        ->save();
                }catch (\Exception $e){
                    $this->_logger->error(__('Disengage Profile error when cancel order #%1, message: %2', $order->getIncrementId(), $e->getMessage()));
                }
            }
        }

        return $this;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @return $this
     */
    protected function updateToConsumerDb(\Riki\Subscription\Model\Profile\Profile $profile){
        $listProfileIds = $this->getActiveProfileIdsOfSpecialCustomer($profile->getCustomerId());

        if(
            count($listProfileIds[CourseType::TYPE_SUBSCRIPTION]) == 0
            || count($listProfileIds[CourseType::TYPE_HANPUKAI]) == 0
        ){
            try{

                $codeAttribute = $profile->getCustomer()->getCustomAttribute('consumer_db_id');

                if(!is_null($codeAttribute)){
                    $consumerDbId = $codeAttribute->getValue();

                    if(count($listProfileIds[CourseType::TYPE_SUBSCRIPTION]) == 0){
                        $this->_rikiCustomerRepository->setCustomerSubAPI($consumerDbId, [1131 =>  0]);
                    }

                    if(count($listProfileIds[CourseType::TYPE_HANPUKAI]) == 0){
                        $this->_rikiCustomerRepository->setCustomerSubAPI($consumerDbId, [1137 =>  0]);
                    }
                }

            }catch (\Exception $e){
                $this->_logger->critical($e);
                $this->_logger->error(__('Disengage Profile error when update Consumer DB, message %1', $e->getMessage()));
            }
        }

        return $this;
    }

    /**
     * @param $customerId
     * @return array
     */
    public function getActiveProfileIdsOfSpecialCustomer($customerId){

        /** @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $collection */
        $collection = $this->_subscriptionProfileCollection->create()
            ->addFieldToFilter('customer_id', $customerId);

        $collection->getSelect()
            ->join(
                ['sc'   =>  'subscription_course'], 'sc.course_id = main_table.course_id', 'sc.subscription_type'
            )->where(
                'main_table.status=?', 1
            )->where(
                'main_table.type IS NULL'
            )->where(
                'sc.subscription_type IN(?)', [CourseType::TYPE_SUBSCRIPTION, CourseType::TYPE_HANPUKAI]
            );

        $profileIdToType = [];

        foreach($collection as $profile){
            $profileIdToType[$profile->getId()] = $profile->getSubscriptionType();
        }

        $hanpukaiIds = [];
        $subscriptionIds = [];
        foreach($profileIdToType as $id     =>  $type){
            if($type == CourseType::TYPE_SUBSCRIPTION){
                $subscriptionIds[] = $id;
            }elseif($type == CourseType::TYPE_HANPUKAI){
                $hanpukaiIds[] = $id;
            }
        }

        $result = [
            CourseType::TYPE_SUBSCRIPTION   =>  $subscriptionIds,
            CourseType::TYPE_HANPUKAI   =>  $hanpukaiIds,
        ];

        return $result;
    }
}
