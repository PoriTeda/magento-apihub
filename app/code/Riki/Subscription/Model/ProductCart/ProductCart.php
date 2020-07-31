<?php
namespace Riki\Subscription\Model\ProductCart;

/**
 * Subscription Course data model
 *
 * @method \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart _getResource()
 * @method \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart getResource()
 */
use Riki\Subscription\Api\WebApi\SubProfileCartOrderInterface;
use Riki\Subscription\Api\WebApi\SubProfileCartProductsInterface;

class ProductCart extends \Magento\Framework\Model\AbstractModel implements
    SubProfileCartOrderInterface,
    SubProfileCartProductsInterface

{
    const TABLE = 'subscription_profile_product_cart';

    const PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY = 'profile_stock_point_discount_rate';
    const IS_SIMULATOR_PROFILE_ITEM_KEY = 'is_simulator_profile_item';
    const IS_READY_TO_CALL_DISCOUNT_API_KEY = 'is_ready_to_call_api';
    const ORIGINAL_DELIVERY_DATE = 'original_delivery_date';
    const ORIGINAL_DELIVERY_TIME_SLOT = 'original_delivery_time_slot';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var array
     */
    protected $_cartProducts = [];
    /**
     * @var \Riki\Subscription\Api\Data\ApiProductCartInterfaceFactory
     */
    protected $_productCartDataFactory;
    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;
    /**
     * @var \Riki\Subscription\Logger\LoggerStateProfile
     */
    protected $loggerStateProfile;

    /**
     * ProductCart constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory
     * @param \Riki\Subscription\Model\Version\VersionFactory $versionFactory
     * @param \Riki\Subscription\Api\Data\ApiProductCartInterfaceFactory $apiProductCartInterface
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        \Riki\Subscription\Api\Data\ApiProductCartInterfaceFactory $apiProductCartInterface,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_versionFactory = $versionFactory;
        $this->_timeSlotsFactory = $timeSlotsFactory;
        $this->_logger = $context->getLogger();
        $this->_productCartDataFactory =  $apiProductCartInterface;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->loggerStateProfile = $loggerStateProfile;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart');
    }

    /**
     * {@inheritdoc}
     */
    public function getCartId()
    {
        return $this->getData('cart_id');
    }

    /**
     * {@inheritdoc}
     */
    public function setCartId($id)
    {
        return $this->setData('cart_id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextDeliveryDate()
    {
        return $this->getData('delivery_date');
    }

    /**
     * {@inheritdoc}
     */
    public function setNextDeliveryDate($date)
    {
        return $this->setData('delivery_date', $date);
    }

    /**
     * @return mixed
     */
    public function getOriginalDeliveryDate()
    {
        return $this->getData(self::ORIGINAL_DELIVERY_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalDeliveryDate($originalDeliveryDate)
    {
        return $this->setData(self::ORIGINAL_DELIVERY_DATE, $originalDeliveryDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalDeliveryTimeSlot()
    {
        return $this->getData(self::ORIGINAL_DELIVERY_TIME_SLOT);
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalDeliveryTimeSlot($originalDeliveryTimeSlot)
    {
        return $this->setData(self::ORIGINAL_DELIVERY_TIME_SLOT, $originalDeliveryTimeSlot);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextDeliverySlotID()
    {
        return $this->getData('delivery_time_slot');
    }

    /**
     * {@inheritdoc}
     */
    public function setNextDeliverySlotID($id)
    {
        return $this->setData('delivery_time_slot', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentSelectedShippingAddress()
    {
        return $this->getData('shipping_address_id');
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentSelectedShippingAddress($id)
    {
        return $this->setData('shipping_address_id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProfileCartProducts()
    {
        return $this->_cartProducts;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubProfileCartProducts($subProfileCartProducts)
    {
        $this->_cartProducts = $subProfileCartProducts;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductID()
    {
        return $this->getData('product_id');
    }

    /**
     * {@inheritdoc}
     */
    public function setProductID($id)
    {
        return $this->setData('product_id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductQty()
    {
        return $this->getData('qty');
    }

    /**
     * {@inheritdoc}
     */
    public function setProductQty($qty)
    {
        return $this->setData('qty', $qty);
    }

    public function setProfileId($id)
    {
        return $this->setData('profile_id', $id);
    }

    public function updateProduct($id, $qty)
    {
        if ($this->getProductID() == $id) {
            $this->setProductQty($qty);

            try {
                $this->save();
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            return true;
        }
        return false;
    }

    public function updateCartProducts($profile_id, $data)
    {
        $this->getResource()->removeProductCart($profile_id);
        $minDate = $data[0]['delivery_date'];

        foreach ($data as $key => $product) {
            $cart = $this;
            $cart->setCartId(null);
            $cart->setProfileId($profile_id);
            $cart->setQty($product['qty']);
            $cart->setProductType($product['product_type']);
            $cart->setproductId($product['product_id']);
            $cart->setShippingAddressId($product['shipping_address_id']);
            $cart->setDeliveryDate($product['delivery_date']);
            $cart->setDeliveryTimeSlot($product['delivery_time_slot']);
            try {
                $cart->save();
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }

            if(strtotime($minDate) > strtotime($product['delivery_date'])) {
                $minDate = $product['delivery_date'];
            }
        }
    }

    public function updateVersion($profileId, $profileMoveToId, $data, $changeType)
    {
        $minDate = $data[0]['delivery_date'];
        foreach ($data as $key => $product) {
            if(strtotime($minDate) > strtotime($product['delivery_date'])) {
                $minDate = $product['delivery_date'];
            }
        }

        if ($changeType == 2) {
            /*Update information into primary profile and delete all version related to that profile*/
            $versionModel = $this->_versionFactory->create()->getCollection();
            $versionProfile = $versionModel->addFieldToFilter('rollback_id', $profileId);
            foreach ($versionProfile as $version) {
                $version->setData('status', false);
                try {
                    $version->save();
                } catch (\Exception $e) {
                    $this->_logger->error($e->getMessage());
                }
            }
        } elseif ($changeType == 1) {
            /*Add new version*/
            $dataVersion = [];
            $dataVersion['start_time'] = $minDate;
            $dataVersion['rollback_id'] = $profileId;
            $dataVersion['is_rollback'] = 0;
            $dataVersion['moved_to'] = $profileMoveToId;
            $dataVersion['status'] = true;
            $versionModel = $this->_versionFactory->create();
            $versionModel->setData($dataVersion);

            try {
                $versionModel->save();
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }
    }

    /**
     * @param $addressId
     * @return int
     */
    public function validateAddress ($addressId){

        return $this->getResource()->validateAddress($addressId);;

    }

    public function getDataModel()
    {
        $productCartData = $this->getData();
        $productCartDataObject = $this->_productCartDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $productCartDataObject,
            $productCartData,
            '\Riki\Subscription\Api\Data\ApiProductCartInterface'
        );
        return $productCartDataObject;
    }

    public function getSpotItemIds($profileId)
    {
        $spotItemIds = $this->getResource()->getSpotItemIds($profileId);
        return $spotItemIds;
    }

    /**
     * @return $this
     */
    public function afterSave(){

        parent::afterSave();

        $this->loggerStateProfile->infoProfileCart($this);

        return $this;
    }
    /**
     * @return $this
     */
    public function afterDelete(){

        parent::afterDelete();

        $this->loggerStateProfile->infoProfileCartDeleted($this);

        return $this;
    }
}