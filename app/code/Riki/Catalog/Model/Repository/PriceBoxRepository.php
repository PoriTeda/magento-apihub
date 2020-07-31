<?php
namespace Riki\Catalog\Model\Repository;

class PriceBoxRepository implements \Riki\Catalog\Api\PriceBoxRepositoryInterface
{
    /**
     *  Limit on query product collection
     */
    const PRODUCT_LIMIT = 1000;

    /**
     * [id => qty]
     *
     * @var array
     */
    protected $productIds;

    /**
     * @var \Riki\Catalog\Model\Data\PriceBoxFactory
     */
    protected $priceBoxFactory;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    protected $userContext;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * PriceBoxRepository constructor.
     *
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Catalog\Model\Data\PriceBoxFactory $priceBoxFactory
     */
    public function __construct(
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Catalog\Model\Data\PriceBoxFactory $priceBoxFactory
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->request = $request;
        $this->userContext = $userContext;
        $this->localeFormat = $localeFormat;
        $this->priceCurrency = $priceCurrency;
        $this->productRepository = $productRepository;
        $this->searchHelper = $searchHelper;
        $this->priceBoxFactory = $priceBoxFactory;

        $this->productIds = [];
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Riki\Catalog\Model\Data\PriceBox[]
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        if (!$this->getIsEnabled()) {
            throw new \Magento\Framework\Webapi\Exception(__('This API is disabled'));
        }

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $this->resolveFilterGroup($filterGroup);
        }

        $products = $this->searchHelper
            ->getByEntityId(array_keys($this->productIds))
            ->limit(static::PRODUCT_LIMIT)
            ->execute($this->productRepository);
        $result = [];

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($products as $product) {
            if (!$product instanceof \Magento\Catalog\Model\Product) {
                continue;
            }

            if (!isset($this->productIds[$product->getId()])) {
                continue;
            }

            foreach ($this->productIds[$product->getId()] as $qty) {
                $product->setQty($qty);

                /** @var \Riki\Catalog\Model\Data\PriceBox $priceBox */
                $priceBox = $this->priceBoxFactory->create();
                $priceBox->setId($product->getId());
                $amountValue = $product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getAmount()
                    ->getValue();
                $priceBox->setFinalPrice(floor($amountValue));
                $priceBox->setQty($qty);

                $result[] = $priceBox;
            }
        }

        return $result;
    }

    /**
     * Convert _ to UcFirst
     *
     * @param $string
     *
     * @return string
     */
    protected function dashToUppercase($string)
    {
        return implode('', array_map('ucfirst', explode('_', $string)));
    }

    /**
     * Resolve filter group
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     *
     * @return void
     */
    public function resolveFilterGroup(\Magento\Framework\Api\Search\FilterGroup $filterGroup)
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $method = 'resolveFilter' . $this->dashToUppercase($filter->getField());
            if (!method_exists($this, $method)) {
                continue;
            }

            $this->$method($filter);
        }
    }

    /**
     * Resolve filter id
     *
     * @param \Magento\Framework\Api\Filter $filter
     *
     * @return void
     */
    public function resolveFilterId(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getConditionType() == 'eq') {
            $value = explode('|', $filter->getValue());
            $this->productIds[$value[0]] = [(isset($value[1]) ? $value[1] : 1)];
        } elseif ($filter->getConditionType() == 'in') {
            foreach (explode(',', $filter->getValue()) as $value) {
                $value = explode('|', $value);
                if (isset($this->productIds[$value[0]])) {
                    $this->productIds[$value[0]][] = (isset($value[1]) ? $value[1] : 1);
                    $this->productIds[$value[0]] = array_unique($this->productIds[$value[0]]);
                } else {
                    $this->productIds[$value[0]] = [(isset($value[1]) ? $value[1] : 1)];
                }
            }
        }
    }

    /**
     * Get is enabled
     *
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return true;
    }
}