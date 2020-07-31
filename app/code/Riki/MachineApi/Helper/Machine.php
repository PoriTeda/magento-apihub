<?php
namespace Riki\MachineApi\Helper;


use Magento\Framework\App\Helper\Context;

class Machine extends \Magento\Framework\App\Helper\AbstractHelper
{

    const LIMIT_MACHINES_WILL_LOAD = 'freemachine/multi_machine/default_limit';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    protected $imageFactory;
    protected $emulation;
    protected $courseFactory;
    protected $scopeConfig;
    protected $machineModel;
    protected $shipLeadTimeStockState;
    protected $quoteItemFactory;
    protected $quoteFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;


    public function __construct(
        Context $context,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\ImageFactory $imageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $emulation,
        \Riki\MachineApi\Model\B2CMachineSkus $machineModel,
        \Riki\ShipLeadTime\Api\StockStateInterface $shipLeadTimeStockState,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->courseFactory = $courseFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->imageFactory = $imageFactory;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->scopeConfig = $context->getScopeConfig();
        $this->machineModel = $machineModel;
        $this->shipLeadTimeStockState = $shipLeadTimeStockState;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $courseId
     * @return \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus\Collection
     */
    public function getAllMachineTypeOfCourse($courseId)
    {
        $courseModel = $this->courseFactory->create()->load($courseId);
        return $machineCollection = $courseModel->getListMachineType();
    }

    /**
     * Get list product will show after load ajax
     *
     * @param $typeId
     * @param $currentPage
     * @return array
     */
    public function getLoadMoreMachines($typeId, $currentPage)
    {
        $machineProducts = [];
        $pageSize = (int)$this->scopeConfig->getValue(self::LIMIT_MACHINES_WILL_LOAD);
        $curPage = (int)$currentPage;
        $machine = $this->machineModel->load($typeId);
        $products = $machine->getProducts($curPage, $pageSize);
        foreach ($products as $product) {
            $imageSrc = $this->getSrcImage($product, 'cart_page_product_thumbnail');
            $product->setData('src', $imageSrc);
            $machineProducts[] = $product->getData();
        }
        return $machineProducts;
    }
    /**
     * Get list machine type of list product ids
     * @param array $productIds
     * @return array
     */
    public function getMachineTypeOfProducts(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        $machineType = [];
        $filter = $this->searchCriteriaBuilder
            ->addFilter('store_id', $this->storeManager->getStore()->getId())
            ->addFilter('entity_id', $productIds, 'in')
            ->addFilter('available_subscription',1)
            ->create();
        $productRepository = $this->productRepository->getList($filter);

        foreach ($productRepository->getItems() as $product) {
            $machineType = array_merge($machineType, explode(',', $product->getMachineCategories()));
        }

        $machineType = array_unique($machineType);
        return $machineType;
    }

    /**
     * Build data for ajax template machine
     * @param $courseId
     * @param $selectedMain
     * @return array
     */
    public function buildDataMachineType($courseId, $selectedMain)
    {
        $response = [];

        $productListTypeIds = $this->getMachineTypeOfProducts($selectedMain);
        $machineTypeCollection = $this->getAllMachineTypeOfCourse($courseId);
        $curPage = 0;
        $pageSize = (int)$this->scopeConfig->getValue(self::LIMIT_MACHINES_WILL_LOAD);
        foreach ($machineTypeCollection as $machineType) {
            $machineId = $machineType->getId();

            if (in_array($machineId, $productListTypeIds)) {
                $available = true;
            } else {
                $available = false;
            }
            $machineArr = [
                'id' => $machineId,
                'available' => $available,
                'error_message' => __('In order to rent this machine, you must purchase some products from %1',
                    $machineType->getCategoryErrorMessage()
                )
            ];

            $machineData = $machineType->getData();

            $machineProducts = [];
            if ($listProduct = $machineType->getProducts($curPage, $pageSize)) {
                foreach ($listProduct as $product) {
                    $imageSrc = $this->getSrcImage($product, 'cart_page_product_thumbnail');
                    $product->setData('src', $imageSrc);
                    $machineProducts[] = $product->getData();
                }
            }
            $productData['limit_machines_load'] = (int)$this->scopeConfig->getValue(self::LIMIT_MACHINES_WILL_LOAD);
            $productData['products'] = $machineProducts;
            $response[] = array_merge($machineArr, $machineData, $productData);
        }
        return $response;
    }

    /**
     * Get Image
     *
     * @param $product
     * @param $imageId
     * @param null $storeId
     * @return mixed
     */
    public function getSrcImage($product, $imageId, $storeId = null)
    {
        $image = null;
        if (!$storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        $appEmulation = $this->emulation;
        $appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $imageUrl = $this->imageFactory->create()
            ->init($product, $imageId);
        $imageSrc = $imageUrl->getUrl();

        $appEmulation->stopEnvironmentEmulation();
        return $imageSrc;
    }

    public function getMachineTypeOfMachine($machines)
    {
        $listMachineType = $this->machineModel->getMachineTypeOfMachine($machines);
        $allMachineTypeOfMachine = [];
        foreach ($listMachineType as $machineType) {
            $allMachineTypeOfMachine[] = $machineType['type_id'];
        }

        return $allMachineTypeOfMachine;
    }

    /**
     * @param $quote
     * @return bool
     */
    public function hasMachineInCart($quote)
    {
        $data = $this->getMainProductAndMachineFromQuote($quote);
        $machines = isset($data['machine']) ? $data['machine'] : [];
        if (!empty($machines)) {
            return true;
        }
        return false;
    }

    /**
     * Return array product and machine
     *
     * @param $quote
     * @return array
     */
    public function getMainProductAndMachineFromQuote($quote)
    {
        $allItem = $quote->getAllItems();
        $machines = [];
        $mainProducts = [];
        foreach ($allItem as $itemCart) {
            $buyRequest = $itemCart->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($itemCart->getData('prize_id')) {
                continue;
            }
            if ($buyRequest->getData('is_multiple_machine')) {
                $machines[] = [
                    'machine_type_id' => isset($buyRequest['options']['machine_type_id']) ?
                        $buyRequest['options']['machine_type_id']: '',
                    'product' => $itemCart->getProduct()
                ];
                continue;
            }

            if ($itemCart->getData('is_riki_machine')) {
                continue;
            }
            $mainProducts[] = $itemCart->getProduct();
        }

        return [
            'product' => $mainProducts,
            'machine' => $machines
        ];
    }

    /**
     * @param $item
     * @return int
     */
    public function getMachineTypeIdByItem($item)
    {
        $buyRequest = $item->getBuyRequest();

        if (isset($buyRequest['options']['machine_type_id'])) {
            return intval($buyRequest['options']['machine_type_id']);
        }

        return 0;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isB2cMachineItem($item)
    {
        return $this->getMachineTypeIdByItem($item) > 0;
    }

    /**
     * Method to get list machine types can buy
     * @param \Magento\Quote\Model\Quote $quote
     * @return array|void
     */
    public function getTypesApplicable(\Magento\Quote\Model\Quote $quote)
    {
        if (!$courseId = $quote->getRikiCourseId()) {
            return [];
        }
        $courseResourceModel = $this->courseFactory->create()->getResource();
        $typesOfCourse = $courseResourceModel->getMultiMachine($courseId);
        $typesOfProduct = [];
        if (!$quote->getAllVisibleItems()) {
            return ;
        }
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $types = $quoteItem->getProduct()->getMachineCategories();
            if (!$types) {
                continue;
            }
            $types = explode(',', $types);
            $typesOfProduct = array_merge($typesOfProduct, $types);
        }

        $typesToSelect = array_intersect($typesOfProduct, $typesOfCourse);
        if (!$typesToSelect) {
            return [];
        }
        return $typesToSelect;
    }

    public function getMachinesByMachineType($typeId)
    {
        return $this->machineModel->getMachinesByMachineType($typeId);
    }

    /**
     * Validate quote before submit order
     *
     * return null if cart does not have machine
     * return array if cart has machine is invalid
     * return true if cart is valid
     * @param $quote
     * @param bool $courseModel
     * @return mixed
     */
    public function validateMachineFromQuote($quote)
    {
        if (!$this->skipValidate($quote)) {
            return true;
        }

        $courseId = $quote->getData('riki_course_id');
        $courseModel = $this->courseFactory->create()->load($courseId);

        if ($courseId && $courseModel) {
            if ($courseModel->getSubscriptionType() ==
                \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES
            ) {
                $data = $this->getMainProductAndMachineFromQuote($quote);

                $mainProduct = isset($data['product']) ? $data['product'] : [];
                $machines = isset($data['machine']) ? $data['machine'] : [];

                if (!empty($machines) && !empty($mainProduct)) {
                    $machineTypeOfProduct = [];
                    foreach ($mainProduct as $product) {
                        $machineTypeOfProduct = array_merge($machineTypeOfProduct,
                            explode(',', $product->getMachineCategories())
                        );
                    }
                    $machineTypeOfProduct = array_unique($machineTypeOfProduct);

                    foreach ($machines as $machineId => $machine) {
                        $machineModel = isset($machine['product']) ? $machine['product'] : false;
                        if (!is_object($machineModel) && !$machineModel->getProductId()) {
                            continue;
                        }
                        $arrListMachineType = $this->getMachineTypeOfMachine($machineModel->getId());
                        $invalidMachineType = array_intersect($arrListMachineType, $machineTypeOfProduct);
                        if (!$invalidMachineType) {
                            /** For case machine product do not belong machine type */
                            $result = [
                                'machine_product_name' => $machineModel->getName()
                            ];
                            return $result;
                        }
                    }
                    return true;
                }
                return [];
            }
        }
        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function skipValidate($quote)
    {
        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return false;
        }

        if (!$quote->getData(\Riki\Subscription\Model\Constant::QUOTE_RIKI_COURSE_ID)) {
            return false;
        }

        if ($quote->getIsOosOrder()) {
            return false;
        }

        if ($quote->getData(\Riki\Subscription\Helper\Order\Data::IS_PROFILE_GENERATED_ORDER_KEY)) {
            return false;
        }
        //case Machine Not Required
        if ($this->checkoutSession->getMachineNotRequired()) {
            return false;
        }
        return true;
    }
    /**
     * Remove machine product invalid in quote
     *
     * @param $quote
     * @param bool $courseModel
     */
    public function removeMachineInvalid($quote)
    {
        if ($quote) {
            $courseId = $quote->getData('riki_course_id');
            $courseModel = $this->courseFactory->create()->load($courseId);

            if ($courseId && $courseModel) {
                if ($courseModel->getSubscriptionType() ==
                    \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES
                ) {
                    $data = $this->getMainProductAndMachineFromQuote($quote);

                    $mainProduct = isset($data['product']) ? $data['product'] : [];
                    $machines = isset($data['machine']) ? $data['machine'] : [];

                    if (!empty($machines) && !empty($mainProduct)) {
                        $machineTypeOfProductMain = [];
                        foreach ($mainProduct as $product) {
                            $machineTypeOfProductMain = array_merge(
                                $machineTypeOfProductMain,
                                explode(',', $product->getMachineCategories())
                            );
                        }
                        $machineTypeOfProductMain = array_unique($machineTypeOfProductMain);

                        foreach ($machines as $machineId => $machineProduct) {
                            $machineProductModel = isset($machineProduct['product']) ? $machineProduct['product'] : false;
                            if (!is_object($machineProductModel) && !$machineProductModel->getId()) {
                                continue;
                            }
                            $arrListMachineType = $this->getMachineTypeOfMachine($machineProductModel->getId());
                            if (empty(array_intersect($arrListMachineType, $machineTypeOfProductMain))) {
                                $quote->getItemByProduct($machineProductModel)->delete();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function getOosB2cMachineItems(\Magento\Quote\Model\Quote $quote)
    {
        $result = [];

        $b2cMachines = [];
        $nonB2cMachines = [];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($machineTypeId = $this->getMachineTypeIdByItem($item)) {
                $b2cMachines[] = $item->setData('machine_type_id', $machineTypeId);
            } else {
                $nonB2cMachines[] = $item;
            }
        }

        if (!empty($b2cMachines)) {
            $newQuote = $this->createTmpQuote($quote, $nonB2cMachines);

            /** @var \Magento\Quote\Model\Quote\Item $b2cMachineItem */
            foreach ($b2cMachines as $b2cMachineItem) {
                if ($this->shipLeadTimeStockState->checkAvailableQty(
                    $newQuote,
                    $b2cMachineItem->getSku(),
                    $b2cMachineItem->getQty()
                )) {
                    $newQuote = $this->addItemToTmpQuote($newQuote, $b2cMachineItem);
                } else {
                    $result[] = $b2cMachineItem;
                }
            }

            unset($newQuote);
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $items
     * @return \Magento\Quote\Model\Quote
     */
    private function createTmpQuote(\Magento\Quote\Model\Quote $quote, $items)
    {
        $newQuoteData = $quote->getData();
        unset($newQuoteData['entity_id']);
        /** @var \Magento\Quote\Model\Quote $newQuote */
        $newQuote = $this->quoteFactory->create()->setData($newQuoteData);

        $addressData = $quote->getShippingAddress()->getData();
        unset($addressData['address_id']);

        $newQuote->getShippingAddress()->addData($addressData);

        $newQuote->setData('items_collection', []);

        foreach ($items as $item) {
            $newQuote = $this->addItemToTmpQuote($newQuote, $item);
        }

        return $newQuote;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return \Magento\Quote\Model\Quote
     */
    private function addItemToTmpQuote(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $newQuoteItemData = $item->getData();
        $newQuoteItemData['item_id'] = null;
        $newQuoteItemData['quote_id'] = null;

        $itemCollection = $quote->getItemsCollection();
        $itemCollection[] = $this->quoteItemFactory->create()->setData($newQuoteItemData);

        $quote->setData('items_collection', $itemCollection);

        return $quote;
    }

}