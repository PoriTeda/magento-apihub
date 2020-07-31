<?php
namespace Riki\Subscription\Model;

use Magento\Framework\Exception\LocalizedException;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay as CaseDisplay;
use Magento\Framework\DataObject;

class Validator implements \Riki\Subscription\Api\Data\ValidatorInterface
{
    const ONLY_APPLY_FOR_THE_FIRST_ORDER = 1;
    const ONLY_APPLY_FOR_THE_SECOND_ORDER = 2;
    const CUSTOM_AMOUNT_FOR_EACH_ORDER_TIME = 3;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    protected $profileId;

    protected $isGenerate = false;

    protected $courseId;

    protected $courseData = [];

    protected $profileData = [];

    protected $productCarts = [];

    protected $restrictionOptionType = 1;

    /**
     * @var CaseDisplay
     */
    protected $caseDisplay;

    /**
     * Validate constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->courseFactory = $courseFactory;
        $this->profileRepository = $profileRepository;
        $this->caseDisplay = $caseDisplay;
    }

    /**
     * @return mixed
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
        return $this;
    }

    /**
     * @return array
     */
    public function getProductCarts(): array
    {
        return $this->productCarts;
    }

    /**
     * @param array $productCarts
     * @return $this
     */
    public function setProductCarts(array $productCarts)
    {
        $this->productCarts = $productCarts;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * @param $courseId
     * @return $this
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGenerate()
    {
        return $this->isGenerate;
    }

    /**
     * @param bool $isGenerate
     * @return $this
     */
    public function setIsGenerate(bool $isGenerate)
    {
        $this->isGenerate = $isGenerate;
        return $this;
    }

    /**
     * @return int
     */
    private function getRestrictionOptionType(): int
    {
        return $this->restrictionOptionType;
    }
    /**
     * @return array
     * @throws LocalizedException
     */
    public function validateMaximumQtyRestriction(): array
    {
        $result = ['error' => false, 'product_errors' => [], 'maxQty' => ''];
        if ($this->getProfileId()) {
            $profileId = $this->getProfileId();
            $profileResponse = $this->getProfile($profileId);

            if (!$profileResponse) {
                return $result;
            }

            $courseId = $profileResponse->getCourseId();
            $this->setCourseId($courseId);
        } elseif ($this->getCourseId()) {
            $courseId = $this->getCourseId();
        } else {
            return $result;
        }
        $listProducts = $this->getProductCarts();
        if (empty($listProducts)) {
            return $result;
        }
        $courseData = $this->getCourseData($courseId);
        $restrictionOptionType = $courseData->getData('maximum_qty_restriction_option');
        $this->restrictionOptionType = $restrictionOptionType;
        $resultValidate = false;
        switch ($restrictionOptionType) {
            case self::ONLY_APPLY_FOR_THE_FIRST_ORDER:
                $resultValidate = $this->validateMaximumQty1stOrder($listProducts);
                break;
            case self::ONLY_APPLY_FOR_THE_SECOND_ORDER:
                $resultValidate = $this->validateMaximumQty2ndOrder($listProducts);
                break;
            case self::CUSTOM_AMOUNT_FOR_EACH_ORDER_TIME:
                $resultValidate = $this->validateMaximumQtyCustomOrder($listProducts);
                break;
            default:
                // do nothing
                break;
        }

        if (!$resultValidate) {
            return $result;
        }

        return [
            'error' => true,
            'product_errors' => $resultValidate['product_errors'],
            'maxQty' => $resultValidate['maxQty']
        ];
    }

    /**
     * @param array $productIds
     * @param $maxQty
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getMessageMaximumError(array $productIds, $maxQty)
    {
        $productNames = $this->getProductNameFromId($productIds);
        if (!empty($productNames)) {
            $message = __(
                'The product %product have been selected over %quantity quantity for purchasing.',
                [
                    'product' =>  implode(", ", $productNames),
                    'quantity' => $maxQty
                ]
            );

            return $message;
        }
    }

    /**
     * @param $productIds
     * @return array
     */
    private function getProductNameFromId($productIds): array
    {
        $productNames = [];
        if (!empty($productIds)) {
            $search = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $productIds, 'in')
                ->addFilter('status', 1, 'eq')
                ->create();
            $productRepository = $this->productRepository->getList($search)->getItems();
            foreach ($productRepository as $product) {
                $productNames[] = $product->getName();
            }
        }
        return $productNames;
    }

    /**
     * @param $listProducts
     * @return array|bool
     */
    private function validateMaximumQty1stOrder($listProducts)
    {
        $maxQty = $this->getMaximumQty();
        if ($maxQty <= 0 || $this->getProfileId()) {
            return false;
        }

        if (!empty($listProducts)) {
            return $this->compareQty($listProducts, $maxQty);
        }
        return false;
    }

    /**
     * @param $listProducts
     * @return array|bool
     */
    private function validateMaximumQty2ndOrder($listProducts)
    {
        $maxQty = $this->getMaximumQty();
        if ($maxQty <= 0 || !$this->is2ndOrder()) {
            return false;
        }

        if (!empty($listProducts)) {
            return $this->compareQty($listProducts, $maxQty);
        }
        return false;
    }

    /**
     * @param $listProducts
     * @return array|bool
     */
    private function validateMaximumQtyCustomOrder($listProducts)
    {
        $maxQty = $this->getMaximumQty();
        if ($maxQty <= 0) {
            return false;
        }

        if (!empty($listProducts)) {
            return $this->compareQty($listProducts, $maxQty);
        }
        return false;
    }

    /**
     * @param $listProducts
     * @param $maxQty
     * @return array|bool
     */
    private function compareQty($listProducts, $maxQty)
    {
        $productErrors = false;
        foreach ($listProducts as $product) {
            if ($this->skip($product)) {
                continue;
            }

            if (strtoupper($product->getData('unit_case')) == CaseDisplay::PROFILE_UNIT_CASE) {
                $qty = (int)$product->getData('qty') / (int)$product->getData('unit_qty');
            } else {
                $qty = (int)$product->getData('qty');
            }
            if ($maxQty < $qty) {
                if ($product instanceof \Magento\Catalog\Model\Product) {
                    $productErrors[] = $product->getId();
                } else {
                    $productErrors[] = $product->getData('product_id');
                }
            }
        }
        if (empty($productErrors)) {
            return false;
        }

        return [
            'product_errors' => $productErrors,
            'maxQty' => $maxQty
        ];
    }

    /**
     * @param $product
     * @return bool
     */
    private function skip($product)
    {
        if ($product->getData('parent_item_id')) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    private function getMaximumQty()
    {
        $courseId = $this->getCourseId();
        $courseData = $this->getCourseData($courseId);
        if ($maximumRestrictionData = $courseData->getData('maximum_qty_restriction')) {
            try {
                $options = json_decode($maximumRestrictionData, true);
                if (isset($options['maximum']) && is_array($options['maximum'])) {
                    if (isset($options['maximum']['qty'])) {
                        return (int)$options['maximum']['qty'];
                    }

                    if (isset($options['maximum']['qtys'])) {
                        $nextOrderTimes = $this->getNextOrderTimes();
                        foreach ($options['maximum']['qtys'] as $threshold) {
                            if (isset($threshold['from_order_time']) &&
                                isset($threshold['to_order_time']) &&
                                isset($threshold['qty'])
                            ) {
                                if ($threshold['from_order_time'] <= $nextOrderTimes &&
                                    $threshold['to_order_time'] >= $nextOrderTimes
                                ) {
                                    return (int)$threshold['qty'];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    /**
     * @return bool
     */
    private function is2ndOrder()
    {
        $profileModel = $this->getProfile($this->getProfileId());
        if ($profileModel &&
            ($profileModel->getOrderTimes() == 1 || ($this->isGenerate() && $profileModel->getOrderTimes() == 2))
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    private function getNextOrderTimes()
    {
        $profileModel = $this->getProfile($this->getProfileId());

        if (!$profileModel) {
            return 1;
        }

        if ($this->isGenerate()) {
            $nextOrderTimes = (int)$profileModel->getOrderTimes();
        } else {
            $nextOrderTimes = (int)$profileModel->getOrderTimes() + 1;
        }
        return $nextOrderTimes;
    }

    /**
     * @param $courseId
     * @return mixed|\Riki\SubscriptionCourse\Model\Course
     */
    public function getCourseData($courseId)
    {
        if (!empty($this->courseData) && isset($this->courseData[$courseId])) {
            return $this->courseData[$courseId];
        }
        $objCourse = $this->courseFactory->create()->load($courseId);
        
        $this->courseData[$courseId] = $objCourse;
        
        return $objCourse;
    }

    /**
     * @param $profileId
     * @return mixed|\Riki\Subscription\Api\Data\ApiProfileInterface
     */
    public function getProfile($profileId)
    {
        if (!empty($this->profileData) && isset($this->profileData[$profileId])) {
            return $this->profileData[$profileId];
        }

        try {
            $profileModel = $this->profileRepository->get($profileId);
            $this->profileData[$profileId] = $profileModel;
        } catch (\Exception $e) {
            return false;
        }

        return $profileModel;
    }

    /**
     * @param $listProducts
     * @return mixed
     */
    public function prepareProductData($listProducts)
    {
        $arrResult = [];
        foreach ($listProducts as $productId => $productInfo) {
            $objProduct = new DataObject();
            $objProduct->setData('product_id', $productId);
            $objProduct->setData('qty', $productInfo['qty']);
            $objProduct->setData('unit_case', isset($productInfo['case_display']) ? $productInfo['case_display'] : '');
            $objProduct->setData('unit_qty', isset($productInfo['unit_qty']) ? $productInfo['unit_qty'] : '');

            if (!isset($arrResult[$productId])) {
                $arrResult[$productId] = $objProduct;
            }
        }

        return $arrResult;
    }

    /**
     * @param $quote
     * @return mixed
     */
    public function prepareProductDataByQuote($quote)
    {
        $arrResult = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($this->isItemSkipped($item)) {
                continue;
            }

            $objProduct = new DataObject();
            $objProduct->setData('product_id', $item->getProductId());
            $objProduct->setData('qty', $item->getQty());
            $objProduct->setData('unit_case', $item->getUnitCase());
            $objProduct->setData('unit_qty', $item->getUnitQty());

            if (!isset($arrResult[$item->getProductId()])) {
                $arrResult[$item->getProductId()] = $objProduct;
            }
        }

        return $arrResult;
    }

    /**
     * Check skip validate quote item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isItemSkipped(\Magento\Quote\Model\Quote\Item $item)
    {
        if ($item->getData('is_riki_machine')) {
            return true;
        }

        $buyRequest = $item->getBuyRequest();
        if (isset($buyRequest['options']['ampromo_rule_id'])) {
            return true;
        }

        if ($item->getData('parent_item_id')) {
            return true;
        }
        return false;
    }

    /**
     * @param $productErrors
     * @param $maxQty
     * @param $orderTimes
     * @return \Magento\Framework\Phrase
     */
    public function getMessageErrorValidateMaxQtyForGenerate($productErrors, $maxQty, $orderTimes)
    {
        $productErrors = $this->getProductNameFromId($productErrors);
        if ($this->getRestrictionOptionType() ==
            self::ONLY_APPLY_FOR_THE_SECOND_ORDER
        ) {
            $messageError = __(
                'The product %1 are over %2 quantity for purchasing.',
                implode(", ", $productErrors),
                $maxQty
            );
        } else {
            $messageError = __(
                'Product %1 is over %2 quantity for purchasing order number %3',
                implode(", ", $productErrors),
                $maxQty,
                (int)$orderTimes
            );
        }
        return $messageError;
    }

    /**
     * @param $productPost
     * @param $quote
     * @return mixed|boolean
     */
    public function prepareProductDataForMultipleMachine($productPost, $quote)
    {
        $arrResult = [];
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($this->isItemSkipped($item)) {
                continue;
            }

            $objProduct = new DataObject();
            $objProduct->setData('product_id', $item->getProductId());
            $objProduct->setData('qty', $item->getQty());
            $objProduct->setData('unit_case', $item->getUnitCase());
            $objProduct->setData('unit_qty', $item->getUnitQty());

            if (!array_key_exists($item->getProductId(), $arrResult)) {
                $arrResult[$item->getProductId()] = $objProduct;
            }
        }

        // Merge data of product post to quote item
        foreach ($productPost as $productId => $productQty) {
            if ($productQty > 0) {
                if (!array_key_exists($productId, $arrResult)) {
                    try {
                        $productModel = $this->productRepository->getById($productId);
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        return false;
                    }

                    $unitCase = $this->caseDisplay->getCaseDisplayKey($productModel->getData('case_display'));

                    $objProduct = new DataObject();
                    $objProduct->setData('product_id', $productId);
                    $objProduct->setData('qty', $productQty);
                    $objProduct->setData('unit_case', $unitCase);
                    $objProduct->setData('unit_qty', $productModel->getData('unit_qty'));

                    $arrResult[$productId] = $objProduct;
                } else {
                    $newQty = $productQty + $arrResult[$productId]->getData('qty');
                    $arrResult[$productId]->setData('qty', $newQty);
                }
            }
        }

        return $arrResult;
    }
}