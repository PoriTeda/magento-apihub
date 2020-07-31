<?php

namespace Riki\StockPoint\Model\Api;

use Magento\Framework\Exception\LocalizedException;
use Riki\StockPoint\Api\Data\DeactivateStockPointResponseInterface;
use Riki\StockPoint\Api\Data\StopStockPointResponseInterface;
use Riki\StockPoint\Api\StockPointManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class StockPointManagement implements StockPointManagementInterface
{
    const PROFILE_IDS_BUNCH_SIZE = 100;
    
    const LOCKER = 1;

    const PICKUP = 2;

    const DROPOFF = 3;

    const SUBCARRIER = 4;

    const LENGTH_RANDOM_NONCE = 32;

    /**
     * @var array
     */
    protected $deactivateRowData = [
        'stock_point_profile_bucket_id' => null,
        'stock_point_delivery_type' => null,
        'stock_point_delivery_information' => null,
    ];
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\StockPoint\Api\Data\StockPointInterfaceFactory
     */
    protected $stockPointFactory;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var DeactivateStockPointResponseInterface
     */
    protected $deactivateStockPointResponse;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    
    /**
     * @var \Magento\Catalog\Model\ProductRepository $productRepository
     */
    protected $productRepository;
    
    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Riki\StockPoint\Logger\StockPointLogger
     */
    protected $stockPointLogger;
    /**
     * @var \Riki\StockPoint\Model\StockPointValidator
     */
    protected $stockPointValidator;
    
    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     */
    protected $validateStockPointProduct;
    
    /**
     * @var \Riki\StockPoint\Api\Data\StopStockPointResponseInterface
     */
    protected $stopStockPointRepsonse;
    
    /**
     * @var \Magento\Framework\DB\TransactionFactory $transactionFactory
     */ 
    protected $transactionFactory;
    
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     */
    protected $profileFactory;
    
    /**
     * @var \Riki\StockPoint\Model\StockPointProfileBucket $stockPointProfileBucket
     */ 
    protected $stockPointProfileBucketFactory;
    
    /**
     * @var \Riki\Customer\Helper\Region $regionHelper
     */
    protected $regionHelper;

    /**
     * StockPointManagement constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\StockPoint\Api\Data\StockPointInterfaceFactory $stockPointFactory
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param DeactivateStockPointResponseInterface $deactivateStockPointResponse
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\StockPoint\Logger\StockPointLogger $stockPointLogger
     * @param \Riki\StockPoint\Model\StockPointValidator $stockPointValidator
     * @param StopStockPointResponseInterface $stopStockPointResponse
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\StockPoint\Api\Data\StockPointInterfaceFactory $stockPointFactory,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\StockPoint\Model\StockPointProfileBucketFactory $stockPointProfileBucketFactory,
        \Riki\Customer\Helper\Region $regionHelper,
        DeactivateStockPointResponseInterface $deactivateStockPointResponse,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\StockPoint\Logger\StockPointLogger $stockPointLogger,
        \Riki\StockPoint\Model\StockPointValidator $stockPointValidator,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        StopStockPointResponseInterface $stopStockPointResponse
    ) {
        $this->logger = $logger;
        $this->stockPointFactory = $stockPointFactory;
        $this->transactionFactory = $transactionFactory;
        $this->profileFactory = $profileFactory;
        $this->productRepository = $productRepository;
        $this->regionHelper = $regionHelper;
        $this->stockPointProfileBucketFactory = $stockPointProfileBucketFactory;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->profileRepository = $profileRepository;
        $this->deactivateStockPointResponse = $deactivateStockPointResponse;
        $this->resourceConnection = $resourceConnection;
        $this->stockPointLogger = $stockPointLogger;
        $this->stockPointValidator = $stockPointValidator;
        $this->stopStockPointResponse = $stopStockPointResponse;
    }
    
    
   /**
    * {@inheritdoc}
    */
    public function updateProfileStockpoint(
        \Riki\StockPoint\Api\Data\StockPointProfileUpdateInputDataInterface $inputData
    ) {
        /** @var \Magento\Framework\DB\Transaction $saveTransaction */
        $saveTransaction = $this->transactionFactory->create();
        $response = [];
        
        /** Logging just in case */ 
        $this->logger->info('ProfileUpdate_PostData',[
          "input_data" => (array)$inputData
        ]);
        
        try{
             /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
            $profile = $this->profileFactory->create()->load($inputData->getProfileId());
            if( !$profile instanceof \Riki\Subscription\Model\Profile &&
                !$profile->hasData('profile_id')
             ) {
                 throw new NoSuchEntityException(__("Profile is not found"));
             }
        }
        catch( NoSuchEntityException $e ){
            throw new \Magento\Framework\Webapi\Exception(
                 __("The requested data with profile id {$inputData->getProfileId()} is not existed"),
                9005,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            );
        }
        catch(\Exception $e){
            throw $e; // unhandle global exception case
        }
        
        // validate profile stockpoint input 
        $productCartModel = $this->profileRepository->getListProductCart($profile->getProfileId());
        $productCartItems = $this->convertProductCart($productCartModel);
        
        // Check all products has stock in Hitachi.
        if (!$this->validateStockPointProduct->checkAllProductInStockWareHouse($productCartItems)) {
            throw new \Magento\Framework\Webapi\Exception(
                 __("the profile #{$inputData->getProfileId()} consists of products out of stock on HITACHI"),
                9006,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            );
        }
        
        // All products of sub profile have stock_point_allowed = YES
        // AND Group of Delivery Type = Normal (excluded for all kinds of free gifts)
        if (!$this->validateStockPointProduct->checkAllProductAllowStockPoint($productCartItems)) {
            throw new \Magento\Framework\Webapi\Exception(
                 __("the profile #{$inputData->getProfileId()} consists of products which don't allow stockpoint"),
                9007,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            );
        }
        
        $validator = new \Zend_Validate_InArray([
           \Bluecom\Paygent\Model\Paygent::CODE,
           \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ]);
        
        if(!$validator->isValid($profile->getPaymentMethod())) {
            throw new \Magento\Framework\Webapi\Exception(
                 __("the profile #{$inputData->getProfileId()} payment method is not appropriate (paygent or NP)"),
                9008,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            );
        }
        
        if(!$this->existStockPoint($inputData->getStockPoint()->getStockPointId())) { // stockpoint is not exist
            goto stockPointIsNotExist;
        } else { // stockpoint is existed 
            goto stockPointIsExisted;
        }
        
        stockPointIsNotExist:
            
        $stockPoint = $this->stockPointFactory->create();
        $stockPoint->setData("external_stock_point_id", $inputData->getStockPoint()->getStockPointId());
        
        // Convert csv.stock_point_prefecture to region_id
        $regionId =$this->regionHelper->getRegionIdByName(
            trim($inputData->getStockPoint()->getStockPointPrefecture())
        );
        
        $stockPoint->setData("firstname", $inputData->getStockPoint()->getStockPointFirstname())
            ->setData("lastname", $inputData->getStockPoint()->getStockPointLastname())
            ->setData("firstname_kana", $inputData->getStockPoint()->getStockPointFirstnamekana())
            ->setData("lastname_kana", $inputData->getStockPoint()->getStockPointLastnamekana())
            ->setData("street", $inputData->getStockPoint()->getStockPointAddress())
            ->setData("region_id", $regionId )
            ->setData("postcode", $inputData->getStockPoint()->getStockPointPostcode())
            ->setData("telephone", $inputData->getStockPoint()->getStockPointTelephone());
        
        $stockPoint->addData(
            $this->extensibleDataObjectConverter->toNestedArray(
                $inputData->getStockPoint(),
                [],
                \Riki\StockPoint\Api\Data\ProfileUpdate\StockpointInputDataInterface::class
            )
        );
        $stockPoint->setData('stock_point_id',NULL);
        
            
        goto updateDateToProfile;
            
        stockPointIsExisted:
            
        try {
            $stockPoint = $this->stockPointFactory->create()->load(
                $inputData->getStockPoint()->getStockPointId(), 'external_stock_point_id'
            );
        }
        catch (NoSuchEntityException $e) {
            throw new \Magento\Framework\Webapi\Exception(
                 __("There is no associated bucket for external stockpoint"),
                9010,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            );
        }
        catch (\Exception $e) { // unhandled exception
            throw $e;
        }
        
        goto updateDateToProfile;
            
        updateDateToProfile:
            
        
        /* saving profile bucket */
        $spProfileBucket = $this->stockPointProfileBucketFactory->create()
            ->load($inputData->getBucketId(), 'external_profile_bucket_id');
        
        $profile->setData('stock_point_delivery_type', call_user_func(function($deliveryType){
            switch($deliveryType){
                case "locker":     return self::LOCKER;
                case "dropoff":    return self::DROPOFF;
                case "pickup":     return self::PICKUP;
                case "subcarrier": return self::SUBCARRIER;
                default:           return '';
            }
        }, $inputData->getDeliveryType()));
        $profile->setData('stock_point_delivery_information', $inputData->getCommentForCustomer() );
        $profile->setData('next_delivery_date', $inputData->getNextDeliveryDate());
        $profile->setData('next_order_date', $inputData->getNextOrderDate());
        $profile->setData('auto_stock_point_assign_status', 2);
        
        // calling to save object
        try {
            $stockPoint->getResource()->beginTransaction();
            $spProfileBucket->getResource()->beginTransaction();
            
            if (!$stockPoint->getId()) {
                $stockPoint->save();
            }
            
            if(!$spProfileBucket->getId()) {
                $spProfileBucket->setExternalProfileBucketId($inputData->getBucketId());
                $spProfileBucket->setStockPointId($stockPoint->getId());
                $spProfileBucket->save();
            } 
            
            $profile->setData('stock_point_profile_bucket_id', $spProfileBucket->getId());
            $saveTransaction->addObject($profile);
            
            $deliveryTimeSlot = $inputData->getDeliveryTime();
            if ($deliveryTimeSlot !== -1) {
                $deliveryTimeSlotId = $this->stockPointValidator->getDeliveryTimeSlotId($deliveryTimeSlot);
            } else {
                $deliveryTimeSlotId = null;
            }
            
            foreach ($productCartModel->getItems() as $productCartItem) {
                // Update this profile product cart
                $productCartItem->setData('delivery_date', $inputData->getNextDeliveryDate());
                $productCartItem->setData('delivery_time_slot',$deliveryTimeSlotId );
                $productCartItem->setData('stock_point_discount_rate', $inputData->getCurrentDiscountRate());
                $saveTransaction->addObject($productCartItem);
            }
            
            $stockPoint->getResource()->commit();
            $spProfileBucket->getResource()->commit();
            $saveTransaction->save();
        } catch(\Exception $e) {
            $stockPoint->getResource()->rollBack();
            $spProfileBucket->getResource()->rollBack();
            throw $e;
        }
        
        $response = [
            "result" => __("Update successfully for profile #{$inputData->getProfileId()}") 
        ];
        
        return [$response];
    }
    
    /**
     * Convert Product Cart
     *
     * @param $productCartModel
     *
     * @return mixed
     */
    public function convertProductCart($productCartModel)
    {
        $productCartItems = [];
        foreach ($productCartModel->getItems() as $product) {
            if ($product->getData(\Riki\Subscription\Model\Profile\Profile::PARENT_ITEM_ID) == 0) {
                /** @var */
                $productObj = $this->productRepository->getById($product->getData('product_id'));
                $productCartItems[] = [
                    'product' => $productObj,
                    'qty' => $product->getQty()
                ];
            }
        }

        return $productCartItems;
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate($stockPointId)
    {
        $emptyProfiles = false;
        $this->validateDeactivateParams($stockPointId);
        try {
            $connection = $this->resourceConnection->getConnection('sales');
            $profiles = $this->profileRepository->getProfilesByStockPointId($stockPointId);
            if ($profiles->getSize()) {
                $profileIds = $profiles->getAllIds();
                $bunch = array_chunk($profileIds, self::PROFILE_IDS_BUNCH_SIZE);
                foreach ($bunch as $rowsData) {
                    $connection->update(
                        $connection->getTableName('subscription_profile'),
                        $this->deactivateRowData,
                        ['profile_id IN (?)' => $rowsData]
                    );
                    $messageLog = __(
                        'The SP #%1 has been deactivated successfully, affected profile_ids [%2]',
                        $stockPointId,
                        implode(',', $rowsData)
                    );
                    $this->stockPointLogger->info(
                        $messageLog,
                        ["type" => \Riki\StockPoint\Logger\StockPointLogger::LOG_TYPE_DEACTIVATE_STOCK_POINT]
                    );
                }
                return $this->deactivateStockPointResponse->setResult(
                    __('The stock point have been deactivated successful.')
                );
            } else {
                $emptyProfiles = true;
            }
        } catch (\Exception $e) {
            if (!$e instanceof LocalizedException) {
                $this->logger->error($e);
            }
            throw new \Magento\Framework\Webapi\Exception(__('There are some thing wrong in the system.'), 9000);
        }
        if ($emptyProfiles) {
            throw new \Magento\Framework\Webapi\Exception(
                __('The SP have not belong to any subscription profile.'),
                9004
            );
        }
    }

    /**
     * Validate request data
     * @param $stockPointId
     * @throws \Magento\Framework\Webapi\Exception
     * @return void
     */
    protected function validateDeactivateParams($stockPointId)
    {
        if (!$stockPointId) {
            throw new \Magento\Framework\Webapi\Exception(__('Stock Point ID is required field.'), 9001);
        }
        if (!is_numeric($stockPointId)) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Stock Point ID [%1] is not an integer.', $stockPointId),
                9002
            );
        }
        if (!$this->existStockPoint($stockPointId)) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Stock Point ID [%1] is not existing.', $stockPointId),
                9003
            );
        }
    }

    /**
     * Check if stock point is existing
     * @param string $stockPointId
     * @return boolean
     */
    protected function existStockPoint($stockPointId)
    {
        $stockPoint = $this->stockPointFactory->create()->load($stockPointId, 'external_stock_point_id');
        if ($stockPoint->getId()) {
            return true;
        }
        return false;
    }
    /**
     * @param int $profileId
     * @param string $nextDeliveryDate
     * @param string $deliveryTimeSlot
     * @param string $isReject
     * @return $this
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function stopStockPoint($profileId, $nextDeliveryDate, $deliveryTimeSlot, $isReject)
    {
        if ($this->stockPointValidator->validateStopParams(
            $profileId,
            $nextDeliveryDate,
            $deliveryTimeSlot,
            $isReject
        )) {
            try {
                $connection = $this->resourceConnection->getConnection('sales');
                //get temp profile if have
                $subcriptionProfileLinkedTable = $connection->getTableName('subscription_profile_link');
                $tempProfileId = $connection->fetchOne($connection->select()
                    ->from($subcriptionProfileLinkedTable, ['linked_profile_id'])
                    ->where('profile_id=?', $profileId));
                $stopStockPointProfileRowData = [
                    'auto_stock_point_assign_status' => $isReject,
                    'next_delivery_date' => $nextDeliveryDate,
                    'stock_point_profile_bucket_id' => null,
                    'stock_point_delivery_information' => null,
                    'stock_point_delivery_type' => null
                ];
                $stopStockPointProfileCartRowData = [
                    'delivery_date' => $nextDeliveryDate
                ];
                if (trim($deliveryTimeSlot)) {
                    $deliveryTimeSlotId = $this->stockPointValidator->getDeliveryTimeSlotId($deliveryTimeSlot);
                    $stopStockPointProfileCartRowData['delivery_time_slot'] = $deliveryTimeSlotId;
                } else {
                    $stopStockPointProfileCartRowData['delivery_time_slot'] = null;
                }
                $connection->beginTransaction();
                //update subscription profile
                $connection->update(
                    $connection->getTableName('subscription_profile'),
                    $stopStockPointProfileRowData,
                    ['profile_id =? ' => $profileId]
                );
                //update subscription profile product cart
                $connection->update(
                    $connection->getTableName('subscription_profile_product_cart'),
                    $stopStockPointProfileCartRowData,
                    ['profile_id =? ' => $profileId]
                );
                //remove temp profile
                if ($tempProfileId) {
                    $connection->delete(
                            $connection->getTableName('subscription_profile'),
                            ['profile_id =? ' => $tempProfileId]
                        );
                    $connection->delete(
                        $subcriptionProfileLinkedTable,
                        ['linked_profile_id =? ' => $tempProfileId]
                    );
                }
                $connection->commit();
                return $this->stopStockPointResponse->setResult(
                    __('Stock Point profile [%1] have been stopped successful.', $profileId)
                );
            } catch (\Exception $e) {
                $connection->rollBack();
                if (!$e instanceof LocalizedException) {
                    $this->logger->error($e);
                }
                throw new \Magento\Framework\Webapi\Exception(__('There are some thing wrong in the system.'), 1000);
            }
        }
    }
}
