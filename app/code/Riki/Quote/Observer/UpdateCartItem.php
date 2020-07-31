<?php

namespace Riki\Quote\Observer;

use Riki\Subscription\Model\Constant;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class UpdateCartItem implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    /**
     * @var \Riki\Checkout\Helper\Data
     */
    protected $checkHelper;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    protected $catalogHelper;

    /**
     * @var \Riki\Quote\Logger\LoggerPieceCase
     */
    protected $loggerPieceCase;

    /**
     * UpdateCartItem constructor.
     * @param \Riki\Checkout\Helper\Data $checkHelper
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param \Riki\Quote\Logger\LoggerPieceCase $loggerPieceCase
     */
    public function __construct(
        \Riki\Checkout\Helper\Data $checkHelper,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Riki\Quote\Logger\LoggerPieceCase $loggerPieceCase
    ) {
    
        $this->sessionQuote = $sessionQuote;
        $this->request = $request;
        $this->checkHelper = $checkHelper;
        $this->productRepository = $productRepository;
        $this->catalogHelper = $catalogHelper;
        $this->loggerPieceCase = $loggerPieceCase;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ('order_create' == $this->request->getControllerName()) {
            $this->handleOnBackEndOrderCreate($observer);
        } elseif ('order_edit' == $this->request->getControllerName()) {
            $this->handleOnBackEndOrderEdit($observer);
        } else {
            $this->handleOnFrontEnd($observer);
        }

        $this->checkAddProductPieceCase($observer);
    }

    /**
     * HandleOnBackEnd.
     *
     * @param $observer
     */
    public function handleOnBackEndOrderCreate($observer)
    {
        $arrProductHanpukaiFirstDelivery = [];
        $params = $this->request->getParams();
        $aParamProducts = isset($params['item']) ? $params['item'] : [];
        $AddBySku = isset($params['add_by_sku']) ? $params['add_by_sku'] : [];
        if (!empty($aParamProducts) || !empty($AddBySku)) {
            if (array_key_exists('hanpukai_qty', $params) && $params['hanpukai_qty'] > 0) {
                $arrProductHanpukaiFirstDelivery =
                    $this->checkHelper->getArrProductFirstDeliveryHanpukai($params['course_id']);
            }

            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($observer->getItems() as &$item) {
                list($unitQty,$caseDisplay) = $this->getPieceCaseInfo($item->getProduct());
                $isParentBundle = false;
                if ($item->getProduct()->getData('parent_product_id') > 0) {
                    $iParentId = $item->getProduct()->getData('parent_product_id');
                    $oParentProduct = $this->productRepository->getById($iParentId);
                    if ('bundle' == $oParentProduct->getTypeId()) {
                        $isParentBundle = true;
                    }
                }

                if (isset($aParamProducts[$item->getProduct()->getEntityId()])) { // add product order
                    if ($isParentBundle) {
                        $unitQty = $item->getProduct()->getUnitQty();
                    } else {
                        if (isset($aParamProducts[$item->getProduct()->getEntityId()]['case_display']) &&
                            'cs' == $aParamProducts[$item->getProduct()->getEntityId()]['case_display']
                        ) {
                            $unitQty = $aParamProducts[$item->getProduct()->getEntityId()]['unit_qty'];
                        }
                    }
                } elseif (null != $item->getProduct()->getUnitQty()) { // get default
                    $unitQty = $item->getProduct()->getUnitQty();
                }

                if (isset($aParamProducts[$item->getProduct()->getEntityId()])) { // add product order

                    if ($isParentBundle) {
                        if ($item->getProduct()->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                            $caseDisplay = CaseDisplay::PROFILE_UNIT_CASE;
                        }
                    } else {
                        if (isset($aParamProducts[$item->getProduct()->getEntityId()]['case_display']) &&
                            'cs' == $aParamProducts[$item->getProduct()->getEntityId()]['case_display']
                        ) {
                            $caseDisplay = CaseDisplay::PROFILE_UNIT_CASE;
                        }
                    }
                } elseif (null != $item->getProduct()->getCaseDisplay()) { // get default
                    if ($item->getProduct()->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                        $caseDisplay = CaseDisplay::PROFILE_UNIT_CASE;
                        $isGeneration = $item->getQuote()->getData('is_generate');
                        if (!$isGeneration && !$isParentBundle && !isset($AddBySku[$item->getProduct()->getSku()])) {
                            $item->setData('qty', $item->getQty() * $unitQty);
                        }
                    }
                }

                $item->setData('unit_case', strtoupper($caseDisplay));
                $item->setData('unit_qty', $unitQty);

                if (isset($params['machine_id']) &&
                    $params['machine_id'] == $item->getProductId() and $item->getData('is_riki_machine') != 1
                ) {
                    // remove old machine
                    $quoteItems = $item->getQuote()->getAllVisibleItems();
                    foreach ($quoteItems as $oldItem) {
                        if ($oldItem->getData('is_riki_machine') == 1) {
                            $oldItem->getQuote()->removeItem($oldItem->getId());
                        }
                    }

                    /**
                     * set for new machine
                     */
                    $item->setData('is_riki_machine', 1);
                }
                if (isset($aParamProducts[$item->getProduct()->getEntityId()]) and
                    isset($aParamProducts[$item->getProduct()->getEntityId()]['is_additional'])
                ) {
                    $isAdditional = $aParamProducts[$item->getProduct()->getEntityId()]['is_additional'];
                    if ($isAdditional) {
                        $item->setData('is_addition', 1);
                    }
                }
                if ($item->getProduct()->getIsRikiMachine()) {
                    $item->setData('is_riki_machine', 1);
                }

                $this->handleHanpukaiQuantity($params, $item, $arrProductHanpukaiFirstDelivery);
            }
        }
    }

    /**
     * HandleOnBackEndOrderEdit
     *
     * @param $observer
     */
    public function handleOnBackEndOrderEdit($observer)
    {
        $itemUpdate = [];
        $params = $this->request->getParams();
        foreach ($observer->getItems() as &$item) {
            list($unitQty,$unitCase) = $this->getPieceCaseInfo($item->getProduct());

            $item->setData('unit_case', strtoupper($unitCase));
            $item->setData('unit_qty', $unitQty);
            if (isset($params['machine_id']) &&
                $params['machine_id'] == $item->getProductId() and $item->getData('is_riki_machine') != 1
            ) {
                // remove old machine
                $quoteItems = $item->getQuote()->getAllVisibleItems();
                foreach ($quoteItems as $oldItem) {
                    if ($oldItem->getData('is_riki_machine') == 1) {
                        $oldItem->getQuote()->removeItem($oldItem->getId());
                    }
                }

                // set for new machine
                $item->setData('is_riki_machine', 1);
            }
            $itemUpdate[] = $item;
        }
        if (!empty($itemUpdate)) {
            $observer->setItems($itemUpdate);
        }
    }

    /**
     * @param $params
     * @param $item
     * @param $arrProductHanpukaiFirstDelivery
     */
    protected function handleHanpukaiQuantity($params, $item, $arrProductHanpukaiFirstDelivery)
    {
        if (isset($params['hanpukai_qty']) && $params['hanpukai_qty'] > 1) {
            if ($item->getQuote()->getRikiCourseId() != null) {
                $hanpukaiMultiQty = $params['hanpukai_qty'] + $item->getQuote()->getData(Constant::RIKI_HANPUKAI_QTY);
            } else {
                $hanpukaiMultiQty = $params['hanpukai_qty'];
            }
            if (in_array($item->getProduct()->getId(), array_keys($arrProductHanpukaiFirstDelivery))) {
                $arrProductHanpukaiConfig = $arrProductHanpukaiFirstDelivery[$item->getProduct()->getId()];
                $item->setData('qty', $arrProductHanpukaiConfig['qty'] * $hanpukaiMultiQty);
            }
        }
    }

    /**
     * HandleOnFrontEnd.
     *
     * @param $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleOnFrontEnd($observer)
    {
        // fix collect totals loop bug
        $quote = $this->_getQuote($observer->getItems());

        if (!$quote) {
            return;
        }

        //first check into cart to make sure we cannot have case and ea of same product
        $itemsAvailable = $quote->getAllVisibleItems();
        $itemsAvailableUnit = [];
        if (count($itemsAvailable)) {
            foreach ($itemsAvailable as $itemAvailable) {
                if ($itemAvailable->getItemId()) {
                    $itemsAvailableUnit[$itemAvailable->getProductId()] = $itemAvailable->getUnitCase();
                }
            }
        }
        $requestInfo = $this->request->getPost();

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($observer->getItems() as &$item) {
            $caseDisplay = CaseDisplay::PROFILE_UNIT_PIECE;

            //get more info from product
            $productItem = $item->getProduct();

            $unitQty = 1;

            $isParentBundle = (bool)$item->getParentItemId();

            if ($requestInfo->get('riki_course_id')) { // from subscription
                $dataUnitProducts = $requestInfo->get('data');

                //set product is additional
                if (isset($dataUnitProducts['product'])) {
                    foreach ($dataUnitProducts['product'] as $key => $product) {
                        if (isset($product['qty']) && $product['qty'] > 0) {
                            if (isset($product['is_addition']) &&
                                $product['product_id'] == $item->getProduct()->getId() &&
                                !$isParentBundle
                            ) {
                                if ($product['is_addition']) {
                                    $item->setData('is_addition', 1);
                                }
                                break;
                            }
                        }
                    }
                }
            }

            if ($productItem && $productItem->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                $unitQty = $productItem->getUnitQty() ? $productItem->getUnitQty() : 1;
            }

            $caseDisplay = $this->handleProductCase(
                $requestInfo,
                $item,
                $unitQty,
                $caseDisplay,
                $isParentBundle,
                $productItem
            );

            if (array_key_exists($item->getProductId(), $itemsAvailableUnit) &&
                $itemsAvailableUnit[$item->getProductId()] != null &&
                $itemsAvailableUnit[$item->getProductId()] != strtoupper($caseDisplay)
            ) {
                $message = __('You can\'t have a piece and a case of the same product in the shopping cart');
                throw new \Magento\Framework\Exception\LocalizedException($message);
            } else {
                $item->setData('unit_case', strtoupper($caseDisplay));
                $item->setData('unit_qty', $unitQty);
            }
        }
    }

    /**
     * FindUnitProductQty
     *
     * @param $dataUnitProducts
     * @param $item
     * @return int|null
     */
    public function findUnitProductQty($dataUnitProducts, $item)
    {
        $unitQty = null;
        if ($dataUnitProducts && isset($dataUnitProducts['product'])) {
            foreach ($dataUnitProducts['product'] as $product) {
                if ($product['product_id'] == $item->getProduct()->getId()) {
                    $unitQty = isset($product['unit_qty']) ? $product['unit_qty'] : 1;
                    break;
                }
            }
        }

        return $unitQty;
    }

    /**
     * HandleProductCase
     *
     * @param $requestInfo
     * @param $item
     * @param $unitQty
     * @param $caseDisplay
     * @param $isParentBundle
     * @return string
     */
    public function handleProductCase($requestInfo, $item, $unitQty, $caseDisplay, $isParentBundle, $productItem)
    {
        $reqEaDisplay = ($requestInfo->get('case_display') == 'ea');
        $reqCsDisplay = ($requestInfo->get('case_display') == 'cs');

        // make sure js work properly at addtocart.phtml
        $reqCsQtyDoubleCheck = ($requestInfo->get('qty_cs_double_check') == $requestInfo->get('qty'));
        if ((($reqEaDisplay || ($reqCsDisplay && $reqCsQtyDoubleCheck)) && !$isParentBundle)
            || $requestInfo->get('riki_course_id')
            || $requestInfo->get('riki_multiple_product')
        ) {
            $caseDisplay = CaseDisplay::PROFILE_UNIT_PIECE;
        } elseif (null != $item->getProduct()->getCaseDisplay()) { // get default
            if ($item->getProduct()->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                $caseDisplay = CaseDisplay::PROFILE_UNIT_CASE;
                if ($this->isReorder()) {
                    $item->setData('qty', $item->getQty());
                } else {
                    $isGeneration = $item->getQuote()->getData('is_generate');
                    if (!$isGeneration && !$isParentBundle) {
                        /**
                         * Convert piece ,case of production promotion
                         */
                        $buyRequest = $item->getBuyRequest();
                        if (isset($buyRequest['options']) && isset($buyRequest['options']['ampromo_rule_id']) &&
                            $buyRequest['options']['ampromo_rule_id'] !=''
                        ) {
                            $item->setData('qty', ($item->getQty()/$item->getUnitQty()) * $unitQty);
                        } else {
                            $item->setData('qty', $item->getQty() * $unitQty);
                        }
                    }
                }
            }
        } elseif (null != $productItem->getCaseDisplay()) {
            if ($productItem->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                $qtyRequest = $requestInfo->get('qty');
                if ($item->getQty() && $qtyRequest) {
                    $item->setData('qty', ((($item->getQty()-$qtyRequest)/$unitQty) + $qtyRequest) * $unitQty);
                }
            }
        }

        if ($productItem->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
            $caseDisplay = CaseDisplay::PROFILE_UNIT_CASE;
        } else {
            $caseDisplay = CaseDisplay::PROFILE_UNIT_PIECE;
        }

        return $caseDisplay;
    }

    /**
     * IsReorder
     *
     * @return bool
     */
    public function isReorder()
    {
        $pathInfo = $this->request->getPathInfo();
        if (strpos($pathInfo, "reorder") !== false) {
            return true;
        }
    }

    /**
     * GetQuote
     * $caseDisplay
     * @param $items
     * @return mixed
     */
    protected function _getQuote($items)
    {
        foreach ($items as $item) {
            return $item->getQuote();
        }
    }

    /**
     * @param $observer
     */
    public function checkAddProductPieceCase($observer)
    {
        if ($observer) {
            foreach ($observer->getItems() as &$item) {
                $product = $item->getProduct();
                if ($product && $product->getId()) {
                    list($unitQty,$caseDisplay) = $this->getPieceCaseInfo($product);
                    if ($item->getUnitCase() !== $caseDisplay
                        || (int)$item->getUnitQty() !== (int)$unitQty
                        || (int)$item->getQty() % $unitQty !== 0
                    ) {
                        if (!$item->getUnitCase()) {
                            $item->setUnitCase($caseDisplay);
                        }

                        if (!$item->getUnitQty()) {
                            $item->setUnitQty($unitQty);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getPieceCaseInfo(\Magento\Catalog\Model\Product $product)
    {
        $unitQty = 1;
        $unitCase = CaseDisplay::PROFILE_UNIT_PIECE;

        if ($product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
            $unitQty = max(1, (int)$product->getUnitQty());
            $unitCase = CaseDisplay::PROFILE_UNIT_CASE;
        }

        return [$unitQty, $unitCase];
    }
}
