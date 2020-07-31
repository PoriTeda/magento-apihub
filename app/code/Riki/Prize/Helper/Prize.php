<?php
namespace Riki\Prize\Helper;

use \Riki\Prize\Model\Prize as PrizeModel;
use \Magento\Framework\Stdlib\DateTime\Timezone;
use Riki\Subscription\Model\Emulator\Config;
use Riki\Prize\Api\ConfigInterface;

class Prize extends \Magento\Framework\App\Helper\AbstractHelper
{
    const WBS_TYPE = 'prize_wbs';
    /**
     * @var \Riki\Prize\Model\PrizeFactory;
     */
    protected $_prizeFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $_deliveryTypeDataHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $_addressHelper;
    /**
     * @var \Riki\Checkout\Model\Order\Address\ItemFactory
     */
    protected $_addressItemFactory;

    /**
     * @var \Riki\Prize\Api\PrizeRepositoryInterface
     */
    protected $prizeRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * Prize constructor.
     *
     * @param \Riki\Prize\Api\PrizeRepositoryInterface $prizeRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Riki\Checkout\Model\Order\Address\ItemFactory $addressItemFactory
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\DeliveryType\Helper\Data $deliveryTypeDataHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Riki\Prize\Model\PrizeFactory $prizeFactory
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Prize\Api\PrizeRepositoryInterface $prizeRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Riki\Checkout\Model\Order\Address\ItemFactory $addressItemFactory,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Framework\Registry $registry,
        \Riki\DeliveryType\Helper\Data $deliveryTypeDataHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Riki\Prize\Model\PrizeFactory $prizeFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->prizeRepository = $prizeRepository;
        $this->searchHelper = $searchHelper;
        $this->datetimeHelper = $datetimeHelper;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->_addressItemFactory = $addressItemFactory;
        $this->_addressHelper = $addressHelper;
        $this->_registry = $registry;
        $this->_deliveryTypeDataHelper = $deliveryTypeDataHelper;
        $this->_productFactory = $productFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->_prizeFactory = $prizeFactory;

        parent::__construct($context);
    }

    /**
     * Apply prize to quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return mixed
     */
    public function applyToQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getResource()->getMainTable() == Config::getCartTmpTableName()) {
            // no apply for simulate subscription order
            return $this;
        }

        $customer = $quote->getCustomer();
        if (!$customer->getId()) {
            return $this;
        }
        $consumerDbId = $customer->getCustomAttribute('consumer_db_id');

        if (!$consumerDbId || !$consumerDbId->getValue()) {
            return $this;
        }
        $consumerDbId = $consumerDbId->getValue();

        $prizes = $this->searchHelper
            ->getByConsumerDbId($consumerDbId)
            ->getByStatus(PrizeModel::STATUS_WAITING)
            ->getAll()
            ->execute($this->prizeRepository);
        if (!$prizes) {
            return $this;
        }

        $quoteItemIds = [];
        $failed = [];
        $today = $this->datetimeHelper->getToday();
        $year = $today->format('Y');
        $month = $today->format('m');
        $day = $today->format('d');
        $hour = $today->format('H');

        /** @var \Riki\Prize\Model\Prize $prize */
        foreach ($prizes as $prize) {
            $product = $prize->getProduct(true);
            if (!$product || !$product->getId()) {
                continue;
            }
            $qty = $this->getProductQuantityForOrder($prize, $product);
            if ($prize->canAttach($quote)) {
                $product->setPrice(0);
                $product->addCustomOption('prize_id', $prize->getId());
                try {
                    $quoteItem = $quote->addProduct($product, $qty);
                    $quoteItem->setData('visible_user_account', true);
                    $quoteItem->setData('prize_id', $prize->getId());
                    $quoteItem->setData('foc_wbs', $prize->getData('wbs'));
                    $quote->save();
                    $quoteItemIds[] = $quoteItem->getId();
                    $prize->setData('status', PrizeModel::STATUS_DONE);
                    $prize->save();
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            } else {
                $prize->setData('status', PrizeModel::STATUS_STOCK_SHORTAGE_ERROR);
                $prize->save();
                $failed[] = $prize;
                /* controlled by Email Markting */
                /* Email: Prize attachment error (Business user)*/
                $templateVariables =
                    [
                        'consumer_db_id' => $consumerDbId,
                        'product_sku' => $product->getSku(),
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'hour' =>$hour
                    ];

                try {
                    if ($this->sendEmailOutOfStock($templateVariables)) {
                        $prize->setData('mail_send_date', $this->datetimeHelper->toDb());
                        $prize->save();
                    }
                } catch (\Exception $e) {
                    $this->_logger->debug($e->getMessage());
                }


                $this->_eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
                    'quote' => $quote,
                    'product' => $product,
                    'prize' => $prize,
                ]);
            }
        }
        if (!is_null($this->_registry->registry('prize_apply_quote_quote_item_ids'))) {
            $this->_registry->unregister('prize_apply_quote_quote_item_ids');
        }

        $this->_registry->register('prize_apply_quote_quote_item_ids', $quoteItemIds);
    }

    /**
     * Apply prize to order
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return $this
     */
    public function applyToOrder(\Magento\Sales\Model\Order $order)
    {
        $quoteItemIds = $this->_registry->registry('prize_apply_quote_quote_item_ids');
        if (!$quoteItemIds) {
            return $this;
        }

        $prizeItems = [];
        $items = [];
        $warehouse = [];
        foreach ($order->getAllItems() as $item) {
            if (in_array($item->getData('quote_item_id'), $quoteItemIds)) {
                $prizeItems[] = $item;
            } else {
                $items[] = $item;
            }
            $warehouse[$item->getId()] = null;
        }
        $warehouse = $this->getItemWarehouseByOrder($order, ['warehouse' => $warehouse]);
        $selected = $this->getSelectedItemForShippingPrize($items, ['selected' => [
            'item' => null,
            'address' => null,
            'delivery_type' => null,
            'delivery_date' => null,
        ]]);

        $this->processShipmentForPrize($prizeItems, [
            'selected' => $selected,
            'warehouse' => $warehouse
        ]);

        return $this;
    }

    /**
     * Get warehouse of each item
     *
     * @param \Magento\Sales\Model\Order $order
     * @param  array $params
     * @return array
     */
    public function getItemWarehouseByOrder(\Magento\Sales\Model\Order $order, $params = [])
    {
        $warehouse = isset($params['warehouse']) ? $params['warehouse'] : [];
        try {
            $assignation = \Zend_Json::decode($order->getAssignation());
        } catch (\Exception $e) {
            $assignation['items'] = [];
        }
        if (isset($assignation['items']) && is_array($assignation['items'])) {
            foreach ($assignation['items'] as $itemId => $assign) {
                if (isset($assign['pos']) && is_array($assign['pos'])) {
                    $warehouse[$itemId] = key($assign['pos']);
                }
            }
        }

        return $warehouse;
    }

    /**
     * Select a item which used to calc shipment for prize
     *
     * @param array $items
     * @param  array $params
     * @return array
     */
    public function getSelectedItemForShippingPrize($items = [], $params = [])
    {
        $selected = isset($params['selected']) ? $params['selected'] : [
            'item' => null,
            'address' => null,
            'delivery_type' => null,
            'delivery_date' => null
        ];
        foreach ($items as $item) {
            if (!$selected['item']) {
                $selected['item'] = $item;
            }

            $address = $this->_addressHelper->getOrderAddressByOrderItem($item);
            if (!$address) {
                continue;
            }

            if (!$selected['address']) {
                $selected['address'] = $address;
            }

            if ($address->getData('riki_type_address') == \Riki\Customer\Model\Address\AddressType::OFFICE) {
                $selected['item'] = $item;
                $selected['address'] = $address;
                break;
            }
        }

        if ($selected['item']) {
            $selected['delivery_type'] = $selected['item']->getDeliveryType();
            $selected['delivery_date'] = $selected['item']->getDeliveryDate();
        }

        return $selected;
    }

    /**
     * Process shipment for prize items
     *
     * @param array $prizeItems
     * @param array $params
     * @return bool
     */
    public function processShipmentForPrize($prizeItems = [], $params = [])
    {
        $selected = isset($params['selected']) ? $params['selected'] : [];
        $warehouse = isset($params['warehouse']) ? $params['warehouse'] : [];
        $coolNormalDM = [
            \Riki\DeliveryType\Model\Delitype::COOL,
            \Riki\DeliveryType\Model\Delitype::NORMAl,
            \Riki\DeliveryType\Model\Delitype::DM
        ];

        foreach ($prizeItems as $prizeItem) {
            if ($selected['address']) {
                /** @var \Riki\Checkout\Model\Order\Address\Item $prizeItemAddress */
                $prizeItemAddress = $this->_addressItemFactory->create();
                $prizeItemAddress->importOrderItem($prizeItem);
                $prizeItemAddress->setAddress($selected['address']);
                $prizeItemAddress->save();
            }
            $sameWarehouse = $warehouse[$prizeItem->getId()] == $warehouse[$selected['item']->getId()];
            if ($sameWarehouse && $prizeItem->getDeliveryType() == $selected['delivery_type']) {
                $prizeItem->setData('delivery_date', $selected['delivery_date']);
            } else {
                if ($sameWarehouse
                    && in_array($prizeItem->getDeliveryType(), $coolNormalDM)
                    && in_array($selected['delivery_type'], $coolNormalDM)
                ) {
                    $prizeItem->setData('delivery_date', $selected['delivery_date']);
                } else {
                    $prizeItem->setData('delivery_date', date('Y-m-d')); // this may be wrong
                }
            }
        }

        return true;
    }

    /**
     * Send email in case out of stock
     *
     * @param $vars
     *
     * @return bool
     */
    public function sendEmailOutOfStock($vars = [])
    {
        $enable = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->prize()
            ->email()
            ->oosEnable();
        if (!$enable) {
            return false;
        }

        $recipients = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->prize()
            ->email()
            ->oosTo();
        if (!trim($recipients)) {
            return false;
        }

        $template = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->prize()
            ->email()
            ->oosTemplate();
        if (!$template) {
            return false;
        }


        $recipients = array_map('trim', explode(',', $recipients));
        try {
            $this->inlineTranslation->suspend();
            $this->transportBuilder
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ])
                ->setTemplateIdentifier($template)
                ->setTemplateVars($vars)
                ->addTo($recipients)
                ->getTransport()
                ->sendMessage();
            $this->inlineTranslation->resume();

            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return false;
    }

    /**
     * Get quantity for order
     *
     * @param $prize
     * @param $product
     * @return mixed
     */
    protected function getProductQuantityForOrder($prize, $product)
    {
        $prizeQty = $prize->getData('qty');

        $productUnitQty = 1;

        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $productUnitQty = (int)$product->getUnitQty() ? (int)$product->getUnitQty() : 1;
        }

        return $prizeQty * $productUnitQty;
    }
}
