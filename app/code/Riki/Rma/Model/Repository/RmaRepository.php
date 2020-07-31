<?php
namespace Riki\Rma\Model\Repository;

use Magento\Framework\Exception\LocalizedException;

class RmaRepository extends \Riki\Framework\Model\AbstractRepository implements \Riki\Rma\Api\RmaRepositoryInterface
{
    /**
     * RmaRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Rma\Model\RmaFactory $factory
     */

    protected $loaded = [];

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Magento\Rma\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var \Magento\Rma\Model\Item\Attribute\Source\StatusFactory
     */
    protected $attrSourceFactory;

    /**
     * @var \Magento\Rma\Helper\Data
     */
    protected $rmaDataHelper;

    /**
     * @var \Riki\Rma\Helper\Rma\Item
     */
    protected $rikiRmaItemHelper;

    /**
     * @var \Magento\Rma\Model\Rma\RmaDataMapper
     */
    protected $rmaDataMapper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Riki\Rma\Model\Rma\BundleItem
     */
    protected $rmaBundleItem;

    /**
     * RmaRepository constructor.
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Rma\Model\RmaFactory $factory
     * @param \Magento\Rma\Model\ItemFactory $itemFactory
     * @param \Magento\Sales\Model\Order\ItemFactory $orderItemFactory
     * @param \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $attrSourceFactory
     * @param \Magento\Rma\Helper\Data $rmaDataHelper
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Riki\Rma\Helper\Rma\Item $rikiRmaItemHelper
     * @param \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Riki\Rma\Model\Rma\BundleItem $rmaBundleItem
     * @throws \Exception
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Rma\Model\RmaFactory $factory,
        \Magento\Rma\Model\ItemFactory $itemFactory,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
        \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $attrSourceFactory,
        \Magento\Rma\Helper\Data $rmaDataHelper,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Riki\Rma\Helper\Rma\Item $rikiRmaItemHelper,
        \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\Rma\Model\Rma\BundleItem $rmaBundleItem
    ) {
        $this->itemFactory = $itemFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->orderItemFactory = $orderItemFactory;
        $this->attrSourceFactory = $attrSourceFactory;
        $this->rmaDataHelper = $rmaDataHelper;
        $this->rikiRmaItemHelper = $rikiRmaItemHelper;
        $this->rmaDataMapper = $rmaDataMapper;
        $this->orderRepository = $orderRepository;
        $this->rmaBundleItem = $rmaBundleItem;
        parent::__construct($resultFactory, $factory);
    }

    /**
     * @param int|string $incrementId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByIncrementId($incrementId)
    {
        if (!isset($this->loaded[$incrementId])) {
            /** @var \Magento\Rma\Model\Rma $rmaObject */
            $rmaObject = $this->factory->create();

            $rma = $rmaObject->getCollection()
                ->addFieldToFilter('increment_id', $incrementId)
                ->setPageSize(1)
                ->getFirstItem();

            if (!$rma || !$rma->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('No such entity with increment id %1', $incrementId)
                );
            }

            $this->loaded[$incrementId] = $rma;
        }

        return $this->loaded[$incrementId];
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\Rma\Api\Data\RmaInterface $entity
     *
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function save(\Riki\Rma\Api\Data\RmaInterface $entity)
    {
        if (!$entity->getEntityId()) {
            $entity = $this->_initNewModel($entity);
        }

        $this->resourceModel->save($entity);
        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $id
     *
     * @return int
     */
    public function lockIdForUpdate($id)
    {
        return $this->factory->create()->getResource()->lockIdForUpdate($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param int[] $ids
     *
     * @return int[]
     */
    public function lockIdsForUpdate($ids)
    {
        return $this->factory->create()->getResource()->lockIdsForUpdate($ids);
    }

    /**
     * @param \Riki\Rma\Api\Data\RmaInterface $entity
     * @return $this|\Magento\Rma\Api\Data\RmaInterface
     */
    protected function _initNewModel(\Riki\Rma\Api\Data\RmaInterface $entity)
    {
        $entityData = $this->extensibleDataObjectConverter
            ->toNestedArray($entity, [], 'Riki\Rma\Api\Data\RmaInterface');

        /** @var \Magento\Rma\Model\Rma $rmaModel */
        $rmaModel = $this->factory->create();

        $initData = $this->rmaDataMapper->prepareNewRmaInstanceData([], $this->orderRepository->get($entityData['order_id']));

        $rmaModel->setData($initData);

        foreach ($entityData as $key    =>  $value) {
            if (!is_null($value)) {
                $rmaModel->setData($key, $value);
            }
        }

        if ($entityData['items']) {

            $itemDefaultData = $this->rikiRmaItemHelper->getDefaultData();

            foreach ($entityData['items'] as $index =>  $item) {
                foreach ($itemDefaultData as $key   =>  $value) {
                    $entityData['items'][$index][$key] = $value;
                }
            }
        }

        $items = $this->_createItemsCollection($entityData);
        /** calculate wrapping fee for bundle */
        $wrappingFeeData = $this->rmaBundleItem->getWrappingFeeData($rmaModel, $entityData, $items);
        foreach ($items as $item) {
            $orderItemId = $item->getOrderItemId();
            $orderItem = $this->rmaBundleItem->getOrderItemById($orderItemId);
            if ($parentItemId = $orderItem->getParentItemId()) {
                /** calculate point earn to cancel for bundle */
                $bundleItemEarnedPoint = $this->rmaBundleItem->getBundleItemEarnedPoint($orderItemId, $item);
                $item->setData('bundle_item_earned_point', $bundleItemEarnedPoint);
                if (isset($wrappingFeeData[$parentItemId][$orderItemId])) {
                    /** set wrapping fee for each child item */
                    $item->setData('return_wrapping_fee', $wrappingFeeData[$parentItemId][$orderItemId]);
                }
            }
        }
        return $rmaModel->setItems($items);
    }

    /**
     * @param $entityData
     * @return array
     * @throws LocalizedException
     */
    protected function _createItemsCollection($entityData)
    {
        $itemModels = [];

        foreach ($entityData['items'] as $key => $item) {
            /** @var \Magento\Rma\Model\Item $itemModel */
            $itemModel = $this->itemFactory->create();

            $itemPost = $this->_preparePost($item);

            $itemModel->setData($itemPost)->prepareAttributes($itemPost, $key);

            foreach ($itemModel->getErrors() as $error) {
                throw new LocalizedException(__($error));
            }

            $itemModels[] = $itemModel;
        }

        $this->_checkPost($itemModels, $entityData['order_id'], boolval($entityData['entity_id']));

        return $itemModels;
    }

    /**
     * @param $item
     * @return array
     * @throws LocalizedException
     */
    protected function _preparePost($item)
    {
        $errors = false;
        $preparePost = [];
        $qtyKeys = ['qty_authorized', 'qty_returned', 'qty_approved'];

        ksort($item);
        foreach ($item as $key => $value) {
            if ($key == 'order_item_id') {
                $preparePost['order_item_id'] = (int)$value;
            } elseif ($key == 'qty_requested') {
                $preparePost['qty_requested'] = is_numeric($value) ? $value : 0;
            } elseif (in_array($key, $qtyKeys)) {
                if (is_numeric($value)) {
                    $preparePost[$key] = (double)$value;
                } else {
                    $preparePost[$key] = '';
                }
            } elseif ($key == 'resolution') {
                $preparePost['resolution'] = (int)$value;
            } elseif ($key == 'condition') {
                $preparePost['condition'] = (int)$value;
            } elseif ($key == 'reason') {
                $preparePost['reason'] = (int)$value;
            } elseif ($key == 'reason_other' && !empty($value)) {
                $preparePost['reason_other'] = $value;
            } else {
                $preparePost[$key] = $value;
            }
        }

        /** @var \Magento\Sales\Model\Order\Item $realItem */
        $realItem = $this->orderItemFactory->create()->load($preparePost['order_item_id']);

        $stat = \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING;
        if (!empty($preparePost['status'])) {
            /** @var $status \Magento\Rma\Model\Item\Attribute\Source\Status */
            $status = $this->attrSourceFactory->create();
            if ($status->checkStatus($preparePost['status'])) {
                $stat = $preparePost['status'];
            }
        }

        $preparePost['status'] = $stat;

        $preparePost['product_name'] = $realItem->getName();
        $preparePost['product_sku'] = $realItem->getSku();
        $preparePost['product_admin_name'] = $this->rmaDataHelper->getAdminProductName($realItem);
        $preparePost['product_admin_sku'] = $this->rmaDataHelper->getAdminProductSku($realItem);
        $preparePost['product_options'] = serialize($realItem->getProductOptions());
        $preparePost['is_qty_decimal'] = $realItem->getIsQtyDecimal();

        if ($preparePost['is_qty_decimal']) {
            $preparePost['qty_requested'] = (double)$preparePost['qty_requested'];
        } else {
            $preparePost['qty_requested'] = (int)$preparePost['qty_requested'];

            foreach ($qtyKeys as $key) {
                if (!empty($preparePost[$key])) {
                    $preparePost[$key] = (int)$preparePost[$key];
                }
            }
        }

        if (isset($preparePost['qty_requested']) && $preparePost['qty_requested'] <= 0) {
            $errors = true;
        }

        foreach ($qtyKeys as $key) {
            if (isset($preparePost[$key]) && !is_string($preparePost[$key]) && $preparePost[$key] <= 0) {
                $errors = true;
            }
        }

        if ($errors) {
            throw new LocalizedException(__('There is an error in quantities for item %1.', $preparePost['product_name']));
        }

        return $preparePost;
    }

    /**
     * @param $itemModels
     * @param $orderId
     * @param bool $isUpdate
     * @return bool
     * @throws LocalizedException
     */
    protected function _checkPost($itemModels, $orderId, $isUpdate = false)
    {
        if (!$isUpdate) {
            $availableItems = $this->rmaDataHelper->getOrderItems($orderId);
        } else {
            /** @var $itemResource \Magento\Rma\Model\ResourceModel\Item */
            $itemResource = $this->itemFactory->create()->getResource();
            $availableItems = $itemResource->getOrderItemsCollection($orderId);
        }

        $itemsArray = [];
        foreach ($itemModels as $item) {
            if (!isset($itemsArray[$item->getOrderItemId()])) {
                $itemsArray[$item->getOrderItemId()] = [
                    'qty' => $item->getQtyRequested(),
                    'sku' => $item->getProductSku()
                ];
            } else {
                $itemsArray[$item->getOrderItemId()]['qty'] += $item->getQtyRequested();
            }

            if ($isUpdate) {
                $validation = [];
                foreach (['qty_requested', 'qty_authorized', 'qty_returned', 'qty_approved'] as $tempQty) {
                    if (is_null($item->getData($tempQty))) {
                        if (!is_null($item->getOrigData($tempQty))) {
                            $validation[$tempQty] = (double)$item->getOrigData($tempQty);
                        }
                    } else {
                        $validation[$tempQty] = (double)$item->getData($tempQty);
                    }
                }
                $validation['dummy'] = -1;
                $previousValue = null;
                foreach ($validation as $key => $value) {
                    if (isset($previousValue) && $value > $previousValue) {
                        throw new LocalizedException(__('There is an error in quantities for item %1.', $item->getSku()));
                    }
                    $previousValue = $value;
                }

                //if we change item status i.e. to authorized, then qty_authorized must be non-empty and so on.
                $qtyToStatus = [
                    'qty_authorized' => [
                        'name' => __('Authorized Qty'),
                        'status' => \Magento\Rma\Model\Rma\Source\Status::STATE_AUTHORIZED,
                    ],
                    'qty_returned' => [
                        'name' => __('Returned Qty'),
                        'status' => \Magento\Rma\Model\Rma\Source\Status::STATE_RECEIVED,
                    ],
                    'qty_approved' => [
                        'name' => __('Approved Qty'),
                        'status' => \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED,
                    ],
                ];
                foreach ($qtyToStatus as $qtyKey => $qtyValue) {
                    if ($item->getStatus() === $qtyValue['status']
                        && $item->getOrigData(
                            'status'
                        ) !== $qtyValue['status']
                        && !$item->getData(
                            $qtyKey
                        )
                    ) {
                        throw new LocalizedException(__('%1 for item %2 cannot be empty.', $qtyValue['name'], $item->getSku()));
                    }
                }
            }
        }
        ksort($itemsArray);

        $availableItemsArray = [];
        foreach ($availableItems as $item) {
            $availableItemsArray[$item->getId()] = [
                'sku' => $item->getSku(),
                'qty' => $item->getAvailableQty(),

            ];
        }

        foreach ($itemsArray as $key => $itemData) {
            /*item sku - which will be returned*/
            $itemSku = !empty($itemData['sku']) ? $itemData['sku'] : $key;
            /*item qty - which will be returned*/
            $itemQty = !empty($itemData['qty']) ? $itemData['qty'] : 0;

            /*the case that item will be returned, is not available*/
            if (!array_key_exists($key, $availableItemsArray)) {
                throw new LocalizedException(__('You cannot return %1.', $itemSku));
            }
            /*the case that quantity of item will be returned, is not enough*/
            if (isset($availableItemsArray[$key]) && $availableItemsArray[$key]['qty'] < $itemQty) {
                throw new LocalizedException(__('A quantity of %1 is greater than you can return.', $availableItemsArray[$key]['sku']));
            }
        }

        return true;
    }
}