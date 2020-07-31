<?php
namespace Riki\MachineApi\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;

class B2CMachineSkus extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const TBL_SUBSCRIPTION_COURSE_MACHINE_TYPE_PRODUCT = 'subscription_course_machine_type_product';

    private $machineTypeTable;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    protected $ruleModelFactory;

    /**
     * @var \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor
     */
    protected $ruleProductProcessor;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Bluecom\PaymentCustomer\Model\Source\Config\Customer\Group
     */
    protected $customerGroup;

    /**
     * B2CMachineSkus constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor
     * @param \Magento\CatalogRule\Model\RuleFactory $ruleModelFactory
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor,
        \Magento\CatalogRule\Model\RuleFactory $ruleModelFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Bluecom\PaymentCustomer\Model\Source\Config\Customer\Group $customerGroup
    ) {
        parent::__construct($context, $connectionName);
        $this->ruleModelFactory = $ruleModelFactory;
        $this->ruleProductProcessor = $ruleProductProcessor;
        $this->productFactory = $productFactory;
        $this->courseFactory = $courseFactory;
        $this->customerGroup = $customerGroup;
    }
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_course_machine_type', 'type_id');
    }

    protected function _afterSave(AbstractModel $object)
    {
        if ($object->hasMachines()) {
            $productIds = $object->getMachines();
            $this->bindTypeToMachine($object, $productIds);
        }
        parent::_afterSave($object);
        return $this;
    }

    public function bindTypeToMachine($machineType, $productsData)
    {
        $this->getConnection()->beginTransaction();
        $table = $this->getMachineTypeTable();

        try {
            $this->_multiplyBunchInsertMachine(
                $machineType,
                $productsData,
                $table,
                ['is_free', 'discount_percent', 'wbs', 'sort_order']
            );
            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMachineTypeTable()
    {
        if (!$this->machineTypeTable) {
            $this->machineTypeTable = $this->getTable(self::TBL_SUBSCRIPTION_COURSE_MACHINE_TYPE_PRODUCT);
        }
        return $this->machineTypeTable;
    }

    public function _multiplyBunchInsertMachine($machineType, $productsData, $table, $columns)
    {
        $typeId = $machineType->getId();

        $oldMachines = $this->getOldMachineIds($typeId);

        $insert = array_diff_key($productsData, $oldMachines);
        $delete = array_diff_key($oldMachines, $productsData);

        $update = array_intersect_key($productsData, $oldMachines);

        $connection = $this->getConnection();

        /**
         * Delete machines from course
         */
        if (!empty($delete)) {
            $cond = ['product_id IN(?)' => array_keys($delete), 'type_id=?' => $typeId];
            $connection->delete($table, $cond);
        }

        /**
         * Add machines to course
         */
        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $productId => $postData) {
                $rowData = [
                    'type_id' => (int)$typeId,
                    'product_id' => (int)$productId
                ];

                foreach ($columns as $column) {
                    if (isset($postData[$column])) {
                        $rowData[$column] = $postData[$column];
                    }
                }
                // set value wbs in case add more machine in more page
                if (isset($postData['wbs']) && is_array($postData['wbs'])) {
                    $rowData['wbs'] = $postData['wbs'][$productId];
                }
                // reset discount_amount for is_free machine
                if (isset($postData['is_free']) && $postData['is_free'] == 1) {
                    $rowData['discount_percent'] = 100;
                }
                $data[] = $rowData;
            }
            $connection->insertMultiple($table, $data);
        }

        /**
         * Update product data in course
         */
        if (!empty($update)) {
            foreach ($update as $productId => $postData) {
                $where = ['type_id = ?' => (int)$typeId, 'product_id = ?' => (int)$productId];
                $bind = [];
                foreach ($columns as $column) {
                    if (isset($postData[$column])) {
                        $bind[$column] = $postData[$column];
                    }
                }
                // set value wbs in case add more machine in more page
                if (isset($postData['wbs']) && is_array($postData['wbs'])) {
                    $bind['wbs'] = $postData['wbs'][$productId];
                }
                // reset discount_amount for is_free machine
                if (isset($postData['is_free']) && $postData['is_free'] == 1) {
                    $bind['discount_percent'] = 100;
                }
                if ($bind) {
                    $connection->update($table, $bind, $where);
                }
            }
        }
        $this->renderCatalogRuleForMachineType($machineType, $insert, $delete, $update);
        return $this;
    }

    public function renderCatalogRuleForMachineType($machineType, $insert, $delete, $update)
    {
        //find course has machine type
        $courses = $this->isInSubscriptionCourse($machineType->getId());
        if (!$courses) {
            return;
        }
        foreach ($courses as $courseData) {
            if (!isset($courseData['course_id'])) {
                continue;
            }
            $course = $this->courseFactory->create()->load($courseData['course_id']);
            if (!$course->getId()) {
                continue;
            }
            if ($course->getSubscriptionType() != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                continue;
            }
            $this->renderCatalogRule($course, $insert, $delete, $update);
        }
    }

    public function renderCatalogRule($course, $insert, $delete, $update)
    {
        $needToReindex = false;
        /**
         * Delete catalog rule of product and course
         */
        $ruleModel = $this->ruleModelFactory->create();
        if (!empty($delete)) {
            $needToReindex = true;
            //delete by product id and course id
            foreach ($delete as $productId => $value) {
                if ($ruleId = $ruleModel->getResource()->getMachineCatalogRule($course->getId(), $productId)) {
                    $ruleModel->getResource()->removeRule($ruleId);
                }
            }
        }
        /**
         * update catalog rule for this machine type
         */
        if (!empty($update)) {
            $needToReindex = true;
            foreach ($update as $productId => $machine) {
                $product = $this->productFactory->create()->load($productId);
                $data = [
                    'name' => 'Machine - ' . $course->getName(),
                    'is_active' => 1,
                    'website_ids' => $course->getAvailableWebsites(),
                    'customer_group_ids' => $this->getCustomerGroupIds(),
                    'subscription' => 2, // sub only
                    'apply_subscription_course_and_frequency' => [$course->getId() => $course->getFrequencies()],
                    'subscription_delivery' => 3, // default
                    'simple_action' => 'by_percent',
                    'is_machine' => 1,
                    'machine_id' => $productId,
                    'conditions' => [
                        '1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Combine',
                            'aggregator' => 'all',
                            'value' => '1',
                            'new_child' => ''
                        ],
                        '1--1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Product',
                            'attribute' => 'sku',
                            'operator' => '==',
                            'value' => $product->getSku()
                        ]
                    ]
                ];
                if (isset($machine['discount_percent']) && $machine['discount_percent']) {
                    $data['discount_amount'] = $machine['discount_percent'];
                }
                if (isset($machine['is_free']) && $machine['is_free'] == 1) {
                    $data['discount_amount'] = 100;
                }
                if (isset($machine['wbs']) && $machine['wbs']) {
                    $data['machine_wbs'] = $machine['wbs'];
                }

                $ruleModel = $this->ruleModelFactory->create();

                $ruleId = $ruleModel->getResource()->getMachineCatalogRule($course->getId(), $productId);
                if ($ruleId) {
                    $data['rule_id'] = $ruleId;
                }

                $ruleModel->loadPost($data);
                $ruleModel->save();
            }
        }
        /**
         * Add catalog rule for this machine type
         */
        if (!empty($insert)) {
            $needToReindex = true;
            foreach ($insert as $productId => $machine) {
                $product = $this->productFactory->create()->load($productId);
                $data = [
                    'name' => 'Machine - ' . $course->getName(),
                    'is_active' => 1,
                    'website_ids' => $course->getAvailableWebsites(),
                    'customer_group_ids' => $this->getCustomerGroupIds(),
                    'subscription' => 2, // sub only
                    'apply_subscription_course_and_frequency' => [$course->getId() => $course->getFrequencies()],
                    'subscription_delivery' => 3, // default
                    'simple_action' => 'by_percent',
                    'is_machine' => 1,
                    'machine_id' => $productId,
                    'conditions' => [
                        '1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Combine',
                            'aggregator' => 'all',
                            'value' => '1',
                            'new_child' => ''
                        ],
                        '1--1' => [
                            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Product',
                            'attribute' => 'sku',
                            'operator' => '==',
                            'value' => $product->getSku()
                        ]
                    ]
                ];
                if (isset($machine['discount_percent']) && $machine['discount_percent']) {
                    $data['discount_amount'] = $machine['discount_percent'];
                }
                if (isset($machine['is_free']) && $machine['is_free'] == 1) {
                    $data['discount_amount'] = 100;
                }
                if (isset($machine['wbs']) && $machine['wbs']) {
                    $data['machine_wbs'] = $machine['wbs'];
                }

                $ruleModel = $this->ruleModelFactory->create();

                $ruleId = $ruleModel->getResource()->getMachineCatalogRule($course->getId(), $productId);
                if ($ruleId) {
                    $data['rule_id'] = $ruleId;
                }

                $ruleModel->loadPost($data);
                $ruleModel->save();
            }
        }
        if ($needToReindex) {
            $this->ruleProductProcessor->markIndexerAsInvalid();
        }
    }

    /**
     * Get all customer group ids
     *
     * @return array
     */
    public function getCustomerGroupIds()
    {
        $groupCustomerIds = [];
        $groupCustomers = $this->customerGroup->toOptionArray();
        foreach ($groupCustomers as $option) {
            $groupCustomerIds[] = $option['value'];
        }
        return $groupCustomerIds;
    }

    public function getOldMachineIds($typeId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMachineTypeTable(),
            ['product_id', 'type_id']
        )->where(
            'type_id = :type_id'
        );
        $bind = ['type_id' => (int)$typeId];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    public function getMachinesByType($typeId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_course_machine_type_product')
        )->where(
            'type_id = :type_id'
        )->order('sort_order ASC');
        $bind = ['type_id' => (int)$typeId];

        return $this->getConnection()->fetchAll($select, $bind);
    }

    /**
     * Get all type id of machine
     * @param $machineIds
     * @return array
     */
    public function getMachineTypeOfMachine($machineIds)
    {
        if (empty($machineIds)) {
            return [];
        }
        $select = $this->getConnection()->select('type_id')->from(
            $this->getTable('subscription_course_machine_type_product'),
            "type_id"
        )->where('product_id IN (?)', $machineIds)
            ->order('sort_order ASC');


        return $this->getConnection()->fetchAll($select);
    }

    public function getMachine($typeId, $productId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_course_machine_type_product')
        )->where(
            'type_id = :type_id and product_id = :product_id'
        )->order('sort_order ASC');
        $bind = ['type_id' => (int)$typeId, 'product_id' => (int)$productId];
        return $this->getConnection()->fetchRow($select, $bind);
    }

    public function isInProduct($typeId)
    {
        $products = $this->productFactory->create()->getCollection()
            ->addAttributeToFilter('machine_categories', ['finset' => $typeId]);
        return $products->getItems();
    }

    /**
     * Get list Machine Product Model Of Machine Type
     * @param $machine
     * @return array
     */
    public function getProducts($machine)
    {
        $curPage = (int)$machine->getCurPage();
        $pageSize = (int)$machine->getPageSize();

        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_course_machine_type_product'),
            ['product_id']
        )->where(
            'type_id = :type_id'
        )->order('sort_order');
        $bind = ['type_id' => (int)$machine->getId()];

        $listProductId = $this->getConnection()->fetchCol($select, $bind);
        if (!empty($listProductId)) {
            $listResult = [];
            foreach ($listProductId as $productId) {
                $product = $this->productFactory->create()->load($productId);
                if ($product->getId()
                    && $product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    $listResult[] = $product;
                }
            }

            $start = $curPage * $pageSize;
            $end = $start + $pageSize -1;
            $listResultAfterFilter = [];
            foreach ($listResult as $index => $product) {
                if ($start <= $index && $index <= $end) {
                    $listResultAfterFilter[] = $product;
                }
            }
            return $listResultAfterFilter;
        }
        return [];
    }
    public function isInSubscriptionCourse($typeId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_course_machine_type_link')
        )->where(
            'machine_type_id = :type_id'
        );
        $bind = ['type_id' => (int)$typeId];
        return $this->getConnection()->fetchAll($select, $bind);
    }
}
