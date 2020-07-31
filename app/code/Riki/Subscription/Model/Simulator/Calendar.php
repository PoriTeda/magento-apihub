<?php

namespace Riki\Subscription\Model\Simulator;

use Riki\DeliveryType\Model\Delitype;

/**
 * Get delivery date of calendar
 *
 * Class Calendar
 * @package Riki\Subscription\Model\Simulator
 */
class Calendar implements \Riki\Subscription\Api\Simulator\CalendarInterface
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $calculateDeliveryDate;

    protected $deliveryType;

    protected $bufferDay;

    protected $storeId;

    protected $addressId;

    protected $profileId;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $subProfileHelper;

    /**
     * Calendar constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate
     * @param \Riki\Subscription\Helper\Profile\Data $subProfileHelper
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate,
        \Riki\Subscription\Helper\Profile\Data $subProfileHelper
    ) {
        $this->profileRepository = $profileRepository;
        $this->productRepository = $productRepository;
        $this->calculateDeliveryDate = $calculateDeliveryDate;
        $this->subProfileHelper = $subProfileHelper;
    }

    /**
     * @inheritdoc
     */
    public function getRestrictCalendar($profileId, $addressId)
    {
        $this->addressId = $addressId;
        $this->profileId = $profileId;

        $arrProductInCart = $this->getProductIdOfProfile();
        $deliveryType = $this->getDeliveryType();
        $isExcludeBufferDays = $this->subProfileHelper->getExcludeBufferDays($profileId);
        if ($isExcludeBufferDays)
        {
            $bufferDay = 0;
        } else {
            $bufferDay = $this->getBufferDay();
        }

        $storeId = $this->getStoreId();

        if ($deliveryType != '' && isset($arrProductInCart[$addressId][$deliveryType])) {
            $arrProductInCart = $arrProductInCart[$addressId][$deliveryType];
            return $this->calculateDeliveryDate->getCalendar($addressId, $arrProductInCart, $deliveryType, $bufferDay);
        } else {
            return null;
        }
    }

    /**
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductIdOfProfile()
    {
        $profile = $this->getProfile();
        $productCarts = $profile->getProductCartData();
        $productIds = [];
        foreach ($productCarts as $productCart) {
            $productObj = $this->productRepository->getById($productCart->getData('product_id'));
            $deliveryType = $productObj->getData('delivery_type');
            $productIds[$this->addressId][$deliveryType]['product'][] = [
                'instance' => $productObj,
                'id' => $productCart->getData('product_id'),
                'qty' => $productCart->getData('qty')
            ];
        }
        return $productIds;
    }

    /**
     * @return null|\Riki\Subscription\Api\Data\ApiProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProfile()
    {
        $profile = $this->profileRepository->get($this->profileId);
        if ($profile instanceof \Riki\Subscription\Api\Data\ApiProfileInterface) {
            $profile = $this->profileRepository->get($profile);
        }
        if (!$profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            return null;
        }
        return $profile;
    }

    /**
     * @param $bufferDay
     * @return mixed|void
     */
    public function setBufferDay($bufferDay)
    {
        $this->bufferDay = $bufferDay;
    }

    /**
     * @return mixed
     */
    public function getBufferDay()
    {
        return $this->bufferDay;
    }

    /**
     * @param $deliveryType
     * @return mixed|void
     */
    public function setDeliveryType($deliveryType)
    {
        $this->deliveryType = $deliveryType;
    }

    /**
     * @return mixed
     */
    public function getDeliveryType()
    {
        return $this->deliveryType;
    }

    /**
     * @param $storeId
     * @return mixed|void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param $profileId
     * @return null|\Riki\Subscription\Api\Data\ApiProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkProfileExistOnCustomer($profileId)
    {
        $profile = $this->profileRepository->get($profileId);
        if ($profile instanceof \Riki\Subscription\Api\Data\ApiProfileInterface) {
            $profile = $this->profileRepository->get($profile);
        }

        if (!$profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            return null;
        }

        return $profile;
    }
}
