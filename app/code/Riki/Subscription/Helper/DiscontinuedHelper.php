<?php

namespace Riki\Subscription\Helper;

class DiscontinuedHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const TABLE_HANPUKAI_PRODUCT_FIXED = 'hanpukai_fixed';
    const TABLE_HANPUKAI_PRODUCT_SEQUENCE = 'hanpukai_sequence';
    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $connectionHelper;

    /**
     * DiscontinuedHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context);
        $this->connectionHelper = $connectionHelper;
    }

    /**
     * Validate product before replace
     * { dont replace product which does not belong to any subscription course }
     *
     * @param $productId
     * @return bool
     */
    public function canDiscontinuedProduct($productId)
    {
        /* product is belong to subscription course - hanpukai fixed */
        $hanpukaiFixedproduct = $this->getHanpukaiProduct(
            $productId,
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_FIXED
        );
        if ($hanpukaiFixedproduct) {
            return true;
        }

        /* product is belong to subscription course - hanpukai sequence */
        $hanpukaiSequenceProduct = $this->getHanpukaiProduct(
            $productId,
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE
        );
        if ($hanpukaiSequenceProduct) {
            return true;
        }

        /* product is belong to subscription course category*/
        $productCategory = $this->getProductCategory($productId);
        if ($productCategory) {
            $courseCategory = $this->getCourseByCategory($productCategory);
            if ($courseCategory) {
                return true;
            }
        }

        /* product is belong to subscription profile*/
        $profileProduct = $this->getProfileIdByProductId($productId);
        if ($profileProduct) {
            return true;
        }

        return false;
    }

    /**
     * Get list category of product
     *
     * @param $productId
     * @return array|bool
     */
    public function getProductCategory($productId)
    {
        $connection = $this->connectionHelper->getDefaultConnection();

        $tableName = $connection->getTableName('catalog_category_product');

        /*get list category of this product*/
        $cateId = $connection->fetchCol($connection->select()
            ->from($tableName, ['category_id'])
            ->where('product_id=?', $productId));

        if ($cateId) {
            return $cateId;
        } else {
            return false;
        }
    }

    /**
     * get list course id by list category
     *
     * @param array $categoryList
     * @return array|bool
     */
    public function getCourseByCategory(array $categoryList)
    {
        $connection = $this->connectionHelper->getSalesConnection();

        $tableName = $connection->getTableName('subscription_course_category');

        /*get list course of this category*/
        $courseId = $connection->fetchCol($connection->select()
            ->from($tableName, ['course_id'])
            ->where('category_id IN (?)', $categoryList));

        if ($courseId) {
            return $courseId;
        } else {
            return false;
        }
    }

    /**
     * Get list profile id by product id
     *
     * @param $productId
     * @return array
     */
    public function getProfileIdByProductId($productId)
    {
        $connection = $this->connectionHelper->getSalesConnection();

        $profileProductTable = $connection->getTableName('subscription_profile_product_cart');
        $profileVersion = $connection->getTableName('subscription_profile_version');

        $select = $connection->select()
            ->from(['p'=> $profileProductTable], [])
            /* we need to get main_profile_id, not profile_version_id */
            ->joinLeft(
                ['v'=> $profileVersion],
                'v.moved_to=p.profile_id',
                ['profile_id' => 'IFNULL(v.rollback_id,p.profile_id)']
            )
            ->where('product_id = ?', $productId)
            ->group('profile_id');

        $profileIds = $connection->fetchCol($select);

        return $profileIds;
    }

    /**
     * Get hanpukai product
     *
     * @param int $productId
     * @param string $hanpukaiType { 'fixed', 'sequence' }
     * @return array|bool
     */
    public function getHanpukaiProduct($productId, $hanpukaiType)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $salesConnection */
        $salesConnection = $this->connectionHelper->getSalesConnection();

        /*get hanpukai table by hanpukai type*/
        if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_FIXED) {
            $hanpukaiTable = $salesConnection->getTableName(
                self::TABLE_HANPUKAI_PRODUCT_FIXED
            );
        } else {
            $hanpukaiTable = $salesConnection->getTableName(
                self::TABLE_HANPUKAI_PRODUCT_SEQUENCE
            );
        }

        /*get list hanpukai of this product*/
        $courseId = $salesConnection->fetchCol($salesConnection->select()
            ->from($hanpukaiTable, ['course_id'])
            ->where('product_id = ?', $productId));

        if ($courseId) {
            return $courseId;
        } else {
            return false;
        }
    }
}
