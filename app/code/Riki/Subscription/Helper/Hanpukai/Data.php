<?php
namespace Riki\Subscription\Helper\Hanpukai;

use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

/**
 * Class Data
 * @package Riki\Subscription\Helper\Hanpukai
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;
    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $_courseResource;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $_courseHelper;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResource
     * @param \Riki\SubscriptionCourse\Helper\Data $courseHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResource,
        \Riki\SubscriptionCourse\Helper\Data $courseHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->objectManager = $objectManager;
        $this->_courseFactory = $courseFactory;
        $this->_courseResource = $courseResource;
        $this->_courseHelper = $courseHelper;
        $this->_productFactory = $productFactory;
        $this->_productRepository = $productRepository;
        parent::__construct($context);
    }

    /**
     * Get Product of Hanpukai Subprofle per condition
     *
     * @param $hanpukaiType
     * @param $courseId
     * @param bool $nDelivery
     * @param bool $month
     * @return array
     */
    public function getHanpukaiProductData($hanpukaiType,$courseId,$nDelivery = false){
        $productData =[];
        $course = $this->_courseFactory->create()->load($courseId);
        if($course->getId()){
            switch($hanpukaiType){
                case SubscriptionType::TYPE_HANPUKAI_FIXED:
                    $productData = $this->_courseResource->getHanpukaiFixedProductsData($course);
                    break;
                case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                    $productData = $this->getHanpukaiSequenceProductsData($course,$nDelivery);
                    break;
                default:
                    return [];

            }
        }
        return $productData;
    }

    /**
     * Get Product of Hanpukai Subprofle per condition
     *
     * @param $hanpukaiType
     * @param $courseId
     * @param bool $nDelivery
     * @param bool $month
     * @return array
     */
    public function getHanpukaiProductDataPieceCase($hanpukaiType,$courseId,$nDelivery = false){
        $productData =[];
        $course = $this->_courseFactory->create()->load($courseId);
        if($course->getId()){
            switch($hanpukaiType){
                case SubscriptionType::TYPE_HANPUKAI_FIXED:
                    $productData = $this->_courseResource->getHanpukaiFixedProductsDataPieCase($course);
                    break;
                case SubscriptionType::TYPE_HANPUKAI_SEQUENCE:
                    $productData = $this->getHanpukaiSequenceProductsDataPieceCase($course,$nDelivery);
                    break;
                default:
                    return [];

            }
        }
        return $productData;
    }

    /**
     * Get product of Hanpukai Sequence per course_id and n_delivery
     *
     * @param $courseId
     * @param $nDelivery
     * @return array
     */
    public function getHanpukaiSequenceProductsData($courseId,$nDelivery){
        if($nDelivery == null || $nDelivery == ''){
            return [];
        }
        $arrProduct = $this->_courseResource->getHanpukaiSequenceProductsData($courseId);
        $result = [];
        foreach ($arrProduct as $key => $value) {
            if ($value['delivery_number'] == $nDelivery) {
                $result[$key] = $value['qty'];
            }
        }
        return $result;
    }

    /**
     * Get product of Hanpukai Sequence per course_id and n_delivery
     *
     * @param $courseId
     * @param $nDelivery
     * @return array
     */
    public function getHanpukaiSequenceProductsDataPieceCase($courseId,$nDelivery){
        if($nDelivery == null || $nDelivery == ''){
            return [];
        }
        $arrProduct = $this->_courseResource->getHanpukaiSequenceProductsData($courseId);
        $result = [];
        foreach ($arrProduct as $key => $value) {
            if ($value['delivery_number'] == $nDelivery) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Get product of Hanpukai Sequence per course_id and n_delivery
     *
     * @param $courseId
     * @param $nDelivery
     * @return array
     */
    public function getHanpukaiAllSequenceProductsData($courseId){
        $arrProduct = $this->_courseResource->getHanpukaiSequenceProductsData($courseId);
        $result = [];
        foreach ($arrProduct as $key => $value) {
                $result[$key] = $value;
        }
        return $result;
    }


    /**
     * Get product of Hanpukai month per course_id and month
     *
     * @param $courseId
     * @param $month
     * @return array
     */
    public function getHanpukaiMonthProductsData($courseId,$month){
        if($month == null || $month == ''){
            return [];
        }
        $arrProduct = $this->_courseResource->getHanpukaiMonthProductsData($courseId);
        $result = [];
        foreach($arrProduct as $key => $value) {
            if(isset($value['delivery_month'])) {
                if($value['delivery_month'] == $month) {
                    $result[$key] = $value['qty'];
                }
            }
        }
        return $result;
    }
    /**
     * Hanpukai helper
     */

    public function getSubscriptionCourseType($courseId)
    {
        return $this->_courseHelper->getSubscriptionCourseType($courseId);
    }

    public function getHanpukaiType($courseId)
    {
        return $this->_courseHelper->getHanpukaiType($courseId);
    }

    public function replaceHanpukaiSequenceProduct($courseId, $nDelivery, $productInfo, $multiQty = 1)
    {
        $productNew = $this->getHanpukaiProductDataPieceCase(SubscriptionType::TYPE_HANPUKAI_SEQUENCE, $courseId, $nDelivery);
        $productData = [];
        foreach ($productNew as $productId => $value) {
            $productModel = $this->_productFactory->create()->load($productId);

            $unitCase = (null != $value['unit_case'])?$value['unit_case']:'EA';
            $unitQty = (null != $value['unit_qty'])?$value['unit_qty']:1;

            $productData[] = [
                'profile_id' => $productInfo["profile_id"],
                'qty' => $value['qty']*$multiQty,
                'product_type' => $productModel->getTypeId(),
                'product_id' => $productId,
                'product_options' => json_encode([]),
                'parent_item_id' => '',
                'shipping_address_id' => $productInfo['shipping_address_id'],
                'billing_address_id' => $productInfo['billing_address_id'],
                'delivery_date' => $productInfo['delivery_date'],
                'delivery_time_slot' => '',
                'unit_case' => $unitCase,
                'unit_qty' => $unitQty,
                'created_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
                'updated_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
            ];
        }

        return $productData;
    }
    public function replaceHanpukaiFixedProduct($courseId,$productInfo, $multiQty = 1)
    {
        $productNew = $this->getHanpukaiProductDataPieceCase(SubscriptionType::TYPE_HANPUKAI_FIXED, $courseId);
        $productData = [];
        foreach ($productNew as $productId => $value) {
            $productModel = $this->_productFactory->create()->load($productId);

            $unitCase = (null != $value['unit_case'])?$value['unit_case']:'EA';
            $unitQty = (null != $value['unit_qty'])?$value['unit_qty']:1;

            $productData[] = [
                'profile_id' => $productInfo["profile_id"],
                'qty' => $value['qty']*$multiQty,
                'product_type' => $productModel->getTypeId(),
                'product_id' => $productId,
                'product_options' => json_encode([]),
                'parent_item_id' => '',
                'shipping_address_id' => $productInfo['shipping_address_id'],
                'billing_address_id' => $productInfo['billing_address_id'],
                'delivery_date' => $productInfo['delivery_date'],
                'delivery_time_slot' => '',
                'unit_case' => $unitCase,
                'unit_qty' => $unitQty,
                'created_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
                'updated_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
            ];
        }

        return $productData;
    }

    public function getSubscriptionCourseHelperData()
    {
        return $this->_courseHelper;
    }

    /**
     * @param $profileModel
     * @param $deliveryNumber
     *
     * @return bool
     */
    public function calculateIsSubStop($profileModel, $deliveryNumber)
    {
        $isStop = false;
        $orderTime = $profileModel->getData('order_times') + $deliveryNumber;
        $courseModel = $this->_courseFactory->create()->load($profileModel->getData('course_id'));
        if ($courseModel && $courseModel->getId()) {
            $hanpukaiType = $courseModel->getData('hanpukai_type');
            $hanpukaiMaximumOrderTime = $courseModel->getData('hanpukai_maximum_order_times');
            if ($hanpukaiMaximumOrderTime != 0) {
                if ($orderTime > $hanpukaiMaximumOrderTime) {
                    return true;
                }
            }
            if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
                $arrSubSequenceProduct = $this->_courseResource
                    ->getHanpukaiSequenceProductsData($courseModel);
                $lastSubSequenceDelivery = $this->getLastSubSequenceDelivery($arrSubSequenceProduct);
                if ($orderTime > $lastSubSequenceDelivery) {
                    return true;
                }
            }

        }
        return $isStop;
    }

    /**
     * Get last subscription sequence delivery
     *
     * @param $arrProduct
     *
     * @return int
     */
    public function getLastSubSequenceDelivery($arrProduct)
    {
        $deliveryNumberArr = array();
        foreach ($arrProduct as $key => $value) {
            if(isset($value['delivery_number'])) {
                $deliveryNumberArr[] = $value['delivery_number'];
            }
        }
        $deliveryNumberArr = $this->sort($deliveryNumberArr, count($deliveryNumberArr));
        if (count($deliveryNumberArr) > 0) {
            return $deliveryNumberArr[count($deliveryNumberArr) - 1];
        } else {
            return 0;
        }
    }

    /**
     * Sort Delivery Number
     *
     * @param $arr
     * @param $length
     *
     * @return array
     */
    public function sort($arr, $length)
    {
        for($i=0; $i < $length - 1; $i++) {
            for($j = $i+1 ; $j < $length; $j++) {
                if((int)$arr[$j] < (int)$arr[$i]) {
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }

}