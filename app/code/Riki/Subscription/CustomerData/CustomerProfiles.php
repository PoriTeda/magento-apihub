<?php

namespace Riki\Subscription\CustomerData;

use Riki\Subscription\Model\Profile\Profile;

class CustomerProfiles implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory
     */
    private $profileCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $imageBuilder;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * CustomerProfiles constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $profileCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->customerSession = $customerSession;
        $this->profileCollectionFactory = $profileCollectionFactory;
        $this->dateTime = $dateTime;
        $this->imageBuilder = $imageBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData()
    {
        if ($this->customerSession->isLoggedIn()) {

            $customerId = $this->customerSession->getCustomerId();
            /** @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $profileCollection */
            $profileCollection = $this->profileCollectionFactory->create();
            $profileCollection->addFieldToFilter('customer_id', $customerId);
            $profileCollection->addFieldToFilter('type', [
                ['neq' => Profile::SUBSCRIPTION_TYPE_TMP],
                ['null' => true]
            ])->addFieldToFilter('status', 1);
            $profileCollection->getSelect()->joinLeft(
                ['subscription_course' => 'subscription_course'],
                "main_table.course_id = subscription_course.course_id",
                [
                    'subscription_course.course_name',
                    'subscription_course.allow_skip_next_delivery',
                    'subscription_course.allow_change_product',
                    'subscription_course.is_allow_cancel_from_frontend',
                    'subscription_course.minimum_order_times',
                    'subscription_course.subscription_type',
                ]
            )->where(
                'subscription_course.subscription_type != ?',
                \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
            );
            //should not display disengaged profiles
            $profileCollection->addFieldToFilter('disengagement_user', ['null' => true]);
            $profileCollection->addFieldToFilter('disengagement_date', ['null' => true]);
            $profileCollection->addFieldToFilter('disengagement_reason', ['null' => true]);
            $profileCollection->addFieldToFilter('next_delivery_date', ['gteq' => date('Y-m-d', $this->dateTime->timestamp())]);
            $profileCollection->addOrder('next_delivery_date', 'ASC');
            if($profileCollection->getSize() > 0){
                $minDeliveryDate = $profileCollection->getFirstItem()->getNextDeliveryDate();
                $profileCollection1 = $this->profileCollectionFactory->create();
                $profileCollection1->addFieldToFilter('customer_id', $customerId)
                    ->addFieldToFilter('type', [
                        ['neq' => Profile::SUBSCRIPTION_TYPE_TMP],
                        ['null' => true]
                    ])
                    ->addFieldToFilter('status', 1)
                    ->addFieldToFilter('next_delivery_date', ['eq' => $minDeliveryDate])
                    ->addOrder('next_delivery_date', 'ASC');
                if($profileCollection1->getSize() >= 2) {
                    $sortedProfileCollection = array_slice($this->sortProfileCollectionByTimeslot($profileCollection1), 0, 2);
                } else{
                    $profileCollection->removeItemByKey($profileCollection->getFirstItem()->getData('profile_id'));
                    $minDeliveryDate2 = $profileCollection->getFirstItem()->getNextDeliveryDate();
                    $profileCollection2 = $this->profileCollectionFactory->create();
                    $profileCollection2->addFieldToFilter('customer_id', $customerId)
                        ->addFieldToFilter('type', [
                            ['neq' => Profile::SUBSCRIPTION_TYPE_TMP],
                            ['null' => true]
                        ])
                        ->addFieldToFilter('status', 1)
                        ->addFieldToFilter('next_delivery_date', ['eq' => $minDeliveryDate2]);
                    if($profileCollection2->getSize() > 0) {
                        $sortedProfileCollection = [$profileCollection1->getFirstItem(), $this->sortProfileCollectionByTimeslot($profileCollection2)[0]];
                    } else{
                        $sortedProfileCollection = [$profileCollection1->getFirstItem()];
                    }
                }
                $result = [];

                /** @var \Riki\Subscription\Model\Profile\Profile $profile */
                foreach ($sortedProfileCollection as $profile) {
                    $productCarts = $profile->getProfileProductCart();
                    $timeSlotId = null;
                    $timeSlotName = null;
                    $items = [];
                    foreach($productCarts as $productCart){
                        $timeSlotId = $productCart['NextDeliverySlotID'];
                        $timeSlotName = $productCart['NextDeliverySlotName'];
                        $subprofileCart = $productCart['SubProfileCartProduct'];
                        $product = $this->productRepository->getById($subprofileCart['ProductID']);
                        $items[] = [
                            'id' => $subprofileCart['ProductID'],
                            'name' => $subprofileCart['ProductName'],
                            'qty' => $subprofileCart['ProductQty'],
                            'thumbnail' => $this->imageBuilder->create($product, 'cart_page_product_thumbnail')->getImageUrl()
                        ];
                    }
                    $price = $profile->getTotalProductsPrice();
                    $settings = $profile->getCourseSetting();
                    $isAllowNextDeliveryDate = $settings['is_allow_change_next_delivery'];
                    $nextDeliveryDate = date('Y/m/d', strtotime($profile->getNextDeliveryDate()));
                    $result[] = [
                        'course_id' => $profile->getId(),
                        'course_name' => $profile->getCourseName(),
                        'next_delivery' => $nextDeliveryDate,
                        'time_slot_id' => $timeSlotId,
                        'time_slot_name' => $timeSlotName,
                        'items' => $items,
                        'price' => $price . '円',
                        'changeable' => $isAllowNextDeliveryDate
                    ];
                }

                return array_slice($result, 0, 2);
            }
        }

        return [];
    }

    /**
     * @param $profile1
     * @param $profile2
     * @return int
     */
    private function timeCompare($profile1, $profile2){
        return strcmp($this->getTimeslotName($profile1), $this->getTimeslotName($profile2));
    }

    /**
     * @param $profileCollection
     * @return array
     */
    public function sortProfileCollectionByTimeslot($profileCollection){
        $unspecific = [];
        $morning = [];
        $otherTimeslot = [];
        foreach($profileCollection as $profile){
            $productCarts = $profile->getProfileProductCart();
            $timeSlotId = null;
            $timeSlotName = null;
            foreach($productCarts as $productCart){
                $timeSlotId = $productCart['NextDeliverySlotID'];
                $timeSlotName = $productCart['NextDeliverySlotName'];
                break;
            }
            if($timeSlotId == -1){
                $unspecific[] = $profile;
            } else if(strstr($timeSlotName, '-') === false){
                $morning[] = $profile;
            } else{
                $otherTimeslot[] = $profile;
            }
        }
        usort($otherTimeslot, array($this, 'timeCompare'));
        return array_merge($unspecific, $morning, $otherTimeslot);
    }

    /**
     * @param $profile
     * @return |null
     */
    private function getTimeslotName($profile){
        $productCarts = $profile->getProfileProductCart();
        foreach($productCarts as $productCart){
            return $productCart['NextDeliverySlotName'];
        }
        return null;
    }
}

