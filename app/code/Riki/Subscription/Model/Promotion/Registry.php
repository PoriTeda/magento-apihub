<?php
namespace Riki\Subscription\Model\Promotion;

use Magento\Catalog\Model\Product\Type as ProductType;

class Registry extends \Amasty\Promo\Model\Registry
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Checkout\Model\Session $resourceSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Amasty\Promo\Helper\Item $promoItemHelper
     * @param \Amasty\Promo\Helper\Messages $promoMessagesHelper
     * @param \Magento\Framework\DataObject $dataObject
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct
    (
        \Magento\Framework\Model\Context $context,
        \Magento\Checkout\Model\Session $resourceSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Helper\Messages $promoMessagesHelper,
        \Magento\Framework\DataObject $dataObject,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    )
    {

        parent::__construct(
            $resourceSession,
            $scopeConfig,
            $productFactory,
            $storeManager,
            $promoItemHelper,
            $promoMessagesHelper
        );

        $this->_logger = $context->getLogger();

        $appState = $context->getAppState();

        $sApi = php_sapi_name();
        if ($sApi == 'cli' || ($sApi != 'cli' && $appState->getAreaCode() == 'adminhtml')) {
            $this->_checkoutSession = $dataObject;
        }
        $this->_productRepository = $productRepository;
    }

    /**
     * ResetHandle
     *
     * @return $this
     */
    public function resetHandle(){
        $this->_isHandled = [];
        return $this;
    }

    /**
     * @param $sku
     * @param $qty
     * @param $ruleId
     */
    public function addPromoItem($sku, $qty, $ruleId){
        if ($this->_locked)
            return;

        if (!$this->_hasItems)
            $this->reset();

        $this->_hasItems = true;

        $items = $this->_checkoutSession->getAmpromoItems();

        if ($items === null)
            $items = ['_groups' => []];

        $autoAdd = false;

        $addAutomatically = $this->scopeConfig->isSetFlag(
            'ampromo/general/auto_add',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        try{
            $product = $this->_productRepository->get($sku);
        }catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            $this->_logger->error(__('Amasty free gift: %1', $e->getMessage()));
            return;
        }catch (\Exception $e){
            $this->_logger->critical($e);
            return;
        }

        $unitQty = 1;
        if($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY){
            $unitQty = max(intval($product->getUnitQty()), 1);
        }

        if (!is_array($sku)) {
            if ($addAutomatically) {

                if(!$product->getId() || $product->getSku() != $sku)
                    return;

                $currentWebsiteId = $this->_storeManager->getWebsite()->getId();
                if (!is_array($product->getWebsiteIds())
                    || !in_array($currentWebsiteId, $product->getWebsiteIds())){
                    // Ignore products from other websites
                    return;
                }

                $product->setData(\Riki\Promo\Helper\Data::PRODUCT_GIFT_FLAG_NAME, 1);

                if (!$product || !$product->isInStock() || !$product->isSalable()) {
                    $this->promoMessagesHelper->addAvailabilityError($product);
                } else {
                    if (in_array($product->getTypeId(), [ProductType::TYPE_SIMPLE, ProductType::TYPE_BUNDLE]))
                    {
                        $autoAdd = true;
                    }
                }
            }


            if (isset($items[$sku])) {
                $items[$sku]['qty'] += $qty * $unitQty;
            } else {
                $items[$sku] = [
                    'sku' => $sku,
                    'rule_id'   =>  $ruleId,
                    'qty' => $qty * $unitQty,
                    'unit_qty'  =>  $unitQty,
                    'auto_add' => $autoAdd,
                    'is_chirashi'   =>  $product->getChirashi()
                ];
            }
        }
        else {
            $items['_groups'][$ruleId] = [
                'sku' => $sku,
                'qty' => $qty * $unitQty
            ];
        }

        $this->_checkoutSession->setAmpromoItems($items);
    }
}