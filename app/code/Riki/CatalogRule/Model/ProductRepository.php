<?php
namespace Riki\CatalogRule\Model;

class ProductRepository implements \Riki\CatalogRule\Api\ProductRepositoryInterface
{
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $_profileRepository;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_subscriptionCourse;
    /**
     * @var \Riki\CatalogRule\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $_adjustmentCalculator;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceCurrency;

    /**
     * ProductRepository constructor.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\CatalogRule\Model\ProductFactory $productFactory
     * @param \Riki\SubscriptionCourse\Model\Course $subscriptionCourse
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry,
        \Riki\CatalogRule\Model\ProductFactory $productFactory,
        \Riki\SubscriptionCourse\Model\Course $subscriptionCourse,
        \Riki\Subscription\Api\WebApi\ProfileRepositoryInterface $profileRepository
    )
    {
        $this->_priceCurrency = $priceCurrency;
        $this->_adjustmentCalculator = $adjustmentCalculator;
        $this->_request = $request;
        $this->_registry = $registry;
        $this->_productFactory = $productFactory;
        $this->_subscriptionCourse = $subscriptionCourse;
        $this->_profileRepository = $profileRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() == 'profile_id') {
                    if ($filter->getConditionType() == 'eq') {
                        if (!$this->_registry->registry('profile_id')) {
                            $this->_registry->register('profile_id', $filter->getValue());
                        }
                    }
                }
                if ($filter->getField() == 'frequency_id') {
                    if ($filter->getConditionType() == 'eq') {
                        if (!$this->_registry->registry('frequency_id')) {
                            $this->_registry->register('frequency_id', $filter->getValue());
                        }
                    }
                }
            }
        }

        return $this->getResults();
    }

    /**
     * Get result from search
     *
     * @return array
     */
    protected function getResults()
    {
        $items = [];

        if ($this->_registry->registry('profile_id')) {
            /** @var \Riki\Subscription\Model\Profile\Profile $profile */
            $profile = $this->_profileRepository->load($this->_registry->registry('profile_id'));
            $storeId = $profile->getData('store_id');
            $products = $this->_subscriptionCourse->getResource()->getAllProductByCourse($profile->getData('course_id'),$storeId);
            if (!$this->_registry->registry('subscription_profile_obj')) {
                $this->_registry->register('subscription_profile_obj', $profile);
            }
            if ($this->_registry->registry('frequency_id')) {
                $this->_request->setParam('frequency_id', $this->_registry->registry('frequency_id'));
            }

            /**
             * @var int $key
             * @var \Magento\Catalog\Model\Product $product
             */
            foreach ($products as $key => $product) {
                /** @var \Riki\CatalogRule\Api\Data\ProductInterface $item */
                $item = $this->_productFactory->create();
                $item->setId($product->getId());
                $amount = $this->_adjustmentCalculator->getAmount($product->getFinalPrice(), $product);
                $item->setAmount($amount->getValue());
                $item->setAmountFormatted($this->_priceCurrency->format($amount->getValue()));
                $items[] = $item;
            }
        }

        return $items;
    }
}