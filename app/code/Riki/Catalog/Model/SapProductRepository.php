<?php
namespace Riki\Catalog\Model;

use Magento\Framework\Exception\InputException;
use Riki\Catalog\Api\Data\SapProductInterface;

class SapProductRepository implements \Riki\Catalog\Api\SapProductRepositoryInterface
{
    const CURRENT_PRODUCT = 'sap_current_product';
    const CREATE_PRODUCT_SAP_API = 'create_product_sap_api';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Riki\Catalog\Helper\SapImportValidator
     */
    protected $sapImportValidator;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var \Wyomind\AdvancedInventory\Api\StockRepositeryInterface
     */
    protected $stockRepository;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi
     */
    protected $loggerImportSap;

    /**
     * @var array
     */
    protected $attributeNedUpdate = [];

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * SapProductRepository constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $stockRepository
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Riki\Catalog\Helper\SapImportValidator $sapImportValidator
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi $loggerImportSap
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $stockRepository,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Riki\Catalog\Helper\SapImportValidator $sapImportValidator,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi $loggerImportSap,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->registry = $registry;
        $this->jsonDecoder = $jsonDecoder;
        $this->stockRepository = $stockRepository;
        $this->attributeRepository = $attributeRepository;
        $this->sapImportValidator = $sapImportValidator;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->loggerImportSap = $loggerImportSap;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function save(\Riki\Catalog\Api\Data\SapProductInterface $product)
    {
        $sapProduct = $product;
        $data = [
            SapProductInterface::SKU => $sapProduct->getSku(),
            SapProductInterface::NAME => $sapProduct->getName(),
            SapProductInterface::ATTRIBUTE_SET_ID => $sapProduct->getAttributeSetId(),
            SapProductInterface::PRICE => $sapProduct->getPrice(),
            SapProductInterface::STATUS => $sapProduct->getStatus(),
            SapProductInterface::VISIBILITY => $sapProduct->getVisibility(),
            SapProductInterface::TYPE_ID => $sapProduct->getTypeId(),
            SapProductInterface::WEIGHT => $sapProduct->getWeight(),
            SapProductInterface::QTY => $sapProduct->getQty(),
            SapProductInterface::IS_IN_STOCK => $sapProduct->getIsInStock(),
            SapProductInterface::MATERIAL_TYPE => $sapProduct->getMaterialType(),
            SapProductInterface::DESCRIPTION => $sapProduct->getDescription(),
            SapProductInterface::UNIT_QTY => $sapProduct->getUnitQty(),
            SapProductInterface::DEPTH => $sapProduct->getDepth(),
            SapProductInterface::WIDTH => $sapProduct->getWidth(),
            SapProductInterface::HEIGHT => $sapProduct->getHeight(),
            SapProductInterface::WEIGHT_UNIT => $sapProduct->getWeightUnit(),
            SapProductInterface::DIMENSION_UNIT => $sapProduct->getDimensionUnit(),
            SapProductInterface::SHELF_LIFE_PERIOD => $sapProduct->getShelfLifePeriod(),
            SapProductInterface::PH_CODE => $sapProduct->getPhCode(),
            SapProductInterface::PH1_DESCRIPTION => $sapProduct->getPh1Description(),
            SapProductInterface::PH2_DESCRIPTION => $sapProduct->getPh2Description(),
            SapProductInterface::PH3_DESCRIPTION => $sapProduct->getPh3Description(),
            SapProductInterface::PH4_DESCRIPTION => $sapProduct->getPh4Description(),
            SapProductInterface::PH5_DESCRIPTION => $sapProduct->getPh5Description(),
            SapProductInterface::BH_SAP => $sapProduct->getBhSap(),
            SapProductInterface::UNIT_SAP => $sapProduct->getUnitSap(),
            SapProductInterface::FUTURE_GPS_PRICE => $sapProduct->getFutureGpsPrice(),
            SapProductInterface::FUTURE_GPS_PRICE_FROM => $sapProduct->getFutureGpsPriceFrom(),
            SapProductInterface::GPS_PRICE => $sapProduct->getGpsPrice(),
            SapProductInterface::SALES_ORGANIZATION => $sapProduct->getSalesOrganization()
        ];

        if (!\Zend_Validate::is($data[SapProductInterface::SKU], 'NotEmpty')) {
            throw new InputException(__(InputException::REQUIRED_FIELD, [
                'fieldName' => 'sku'
            ]));
        }

        /** @var \Magento\Catalog\Model\Product $product */
        try {
            $product = $this->productRepository->get($data[SapProductInterface::SKU]);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $product = $this->productFactory->create();
        }

        $isNew = !$product->getId();

        $data = $this->sapImportValidator->filter($data);

        $this->loggerImportSap->info("===== START =====");
        $this->loggerImportSap->info("Data SAP update from api Data");
        if (isset($data[SapProductInterface::UNIT_SAP])) {
            $this->loggerImportSap->info(sprintf(
                SapProductInterface::UNIT_SAP . ': %s',
                $data[SapProductInterface::UNIT_SAP]
            ));
        }
        if (isset($data[SapProductInterface::GPS_PRICE])) {
            $this->loggerImportSap->info(sprintf(
                SapProductInterface::GPS_PRICE . ': %s',
                $data[SapProductInterface::GPS_PRICE]
            ));
        }

        if (isset($data[SapProductInterface::FUTURE_GPS_PRICE])) {
            $this->loggerImportSap->info(sprintf(
                SapProductInterface::FUTURE_GPS_PRICE .': %s',
                $data[SapProductInterface::FUTURE_GPS_PRICE]
            ));
        }

        if (isset($data[SapProductInterface::SALES_ORGANIZATION])) {
            $this->loggerImportSap->info(sprintf(
                SapProductInterface::SALES_ORGANIZATION .': %s',
                $data[SapProductInterface::SALES_ORGANIZATION]
            ));
        }

        if ($isNew) {
            $this->loggerImportSap->info(sprintf(
                'Create product Sku #%s with SAP API',
                $data[SapProductInterface::SKU]
            ));
            $requiredFields = [
                SapProductInterface::SKU,
                SapProductInterface::NAME,
                SapProductInterface::ATTRIBUTE_SET_ID,
                SapProductInterface::PRICE,
                SapProductInterface::STATUS,
                SapProductInterface::VISIBILITY,
                SapProductInterface::TYPE_ID,
                SapProductInterface::WEIGHT,
                SapProductInterface::QTY,
                SapProductInterface::IS_IN_STOCK,
                SapProductInterface::MATERIAL_TYPE,
                SapProductInterface::DESCRIPTION,
                SapProductInterface::UNIT_QTY,
                SapProductInterface::DEPTH,
                SapProductInterface::WIDTH,
                SapProductInterface::HEIGHT,
                SapProductInterface::WEIGHT_UNIT,
                SapProductInterface::DIMENSION_UNIT,
                SapProductInterface::SHELF_LIFE_PERIOD,
                SapProductInterface::PH_CODE,
                SapProductInterface::PH1_DESCRIPTION,
                SapProductInterface::PH2_DESCRIPTION,
                SapProductInterface::PH3_DESCRIPTION,
                SapProductInterface::PH4_DESCRIPTION,
                SapProductInterface::PH5_DESCRIPTION,
                SapProductInterface::BH_SAP,
                SapProductInterface::SALES_ORGANIZATION,
            ];
            $this->sapImportValidator->setRequiredFields($requiredFields);
            $this->sapImportValidator->validate($data);
        } else {
            $this->loggerImportSap->info(sprintf(
                'Update product Sku #%s with SAP API',
                $data[SapProductInterface::SKU]
            ));
            unset($data[SapProductInterface::NAME]);
            unset($data[SapProductInterface::ATTRIBUTE_SET_ID]);
            unset($data[SapProductInterface::PRICE]);
            unset($data[SapProductInterface::STATUS]);
            unset($data[SapProductInterface::VISIBILITY]);
            unset($data[SapProductInterface::TYPE_ID]);
            unset($data[SapProductInterface::QTY]);
            unset($data[SapProductInterface::IS_IN_STOCK]);
            $requiredFields = [
                SapProductInterface::SKU
            ];
            $this->sapImportValidator->setRequiredFields($requiredFields);
            $this->sapImportValidator->validate($data);

            // NED-4642 If future_gps_price_from is empty, do not update , revert current value
            if (isset($data[SapProductInterface::FUTURE_GPS_PRICE_FROM])
                && empty($data[SapProductInterface::FUTURE_GPS_PRICE_FROM])
                && $product->getFutureGpsPriceFrom()
            ) {
                $data[SapProductInterface::FUTURE_GPS_PRICE_FROM] = $product->getFutureGpsPriceFrom();
                $sapProduct->setFutureGpsPriceFrom($product->getFutureGpsPriceFrom());
            }
        }

        foreach ($data as $key => $value) {
            $method = 'set' . $this->sapImportValidator->upperCase($key);
            if (method_exists($product, $method)) {
                $product->$method($value);
            } else {
                $product->setData($key, $value);
            }
        }

        $attributes = $product->getAttributes();
        $defaultStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;

        // convert price
        if ($product->getData(SapProductInterface::UNIT_SAP)) {
            if ($product->getData(SapProductInterface::GPS_PRICE) && isset($attributes['gps_price_ec'])) {
                $this->attributeNedUpdate['gps_price_ec'] = $product->getAttributes()['gps_price_ec']->getAttributeId();
            }

            if ($product->getData(SapProductInterface::FUTURE_GPS_PRICE) && isset($attributes['future_gps_price'])) {
                $attFutureGpsPriceId = $product->getAttributes()['future_gps_price_ec']->getAttributeId();
                $this->attributeNedUpdate['future_gps_price_ec'] = $attFutureGpsPriceId;
            }

            if ($product->getData(SapProductInterface::GPS_PRICE)) {
                $this->attributeNedUpdate['gps_price'] = $product->getAttributes()['gps_price']->getAttributeId();
            }
        }

        if ($isNew) {
            // for product import from SAP, use sku as url_path, admin can change later in admincp
            $product->setData('url_key', $product->formatUrlKey($data[SapProductInterface::SKU]));
            // delivery type is set to Normal by default @see RIKI-3818
            $product->setData('delivery_type', \Riki\DeliveryType\Model\Delitype::NORMAl);
        } else {
            $this->registry->unregister(static::CURRENT_PRODUCT);
            $this->registry->register(static::CURRENT_PRODUCT, [
                'id' => $product->getId(),
                'website_ids' => $product->getWebsiteIds()
            ]);
            $product = $this->processUpdateGpsValue($product, $defaultStoreId);
        }

        $this->registry->unregister(static::CREATE_PRODUCT_SAP_API);
        $this->registry->register(static::CREATE_PRODUCT_SAP_API, [
            'is_new' => $isNew,
            'sku' => $data[SapProductInterface::SKU]
        ]);

        $product = $this->handleDefaultValue($product);

        // save product
        $product = $this->productRepository->save($product);
        if ($isNew) {
            $product = $this->processUpdateGpsValue($product, $defaultStoreId);
        }
        $this->cleanGpsValue($product, $defaultStoreId, $this->attributeNedUpdate);

        $placeId = $this->getDefaultWarehouseId();
        // set stock item
        if ($isNew && $placeId) {
            $multiStock = 1;
            $manageStock = true;
            $qtyInStock = isset($data[SapProductInterface::QTY]) ? $data[SapProductInterface::QTY] : 0;
            $isInStock = isset($data[SapProductInterface::IS_IN_STOCK]) ? $data[SapProductInterface::IS_IN_STOCK] : 1;

            $this->stockRepository->updateStock($product->getId(), $multiStock, $placeId, $manageStock, $qtyInStock);
            $this->stockRepository->updateInventory($product->getId(), $isInStock);
        }

        $this->loggerImportSap->info(sprintf(
            'Product Sku #%s has been imported with SAP API successfully',
            $data[SapProductInterface::SKU]
        ));
        $this->loggerImportSap->info("===== END =====");

        return $sapProduct;
    }

    /**
     * Handle default value for attribute which unexpected
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function handleDefaultValue(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        try {
            $product->getResource()->validate($product);
        } catch (\Magento\Eav\Model\Entity\Attribute\Exception $e) {
            $attributeCode = $e->getAttributeCode();
            $attribute = $this->attributeRepository
                ->get(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE, $attributeCode);
            $sourceOptions = $attribute->getSource() ? $attribute->getSource()->getAllOptions() : [];
            $default = ($sourceOptions && is_array($sourceOptions)) ? array_shift($sourceOptions) : null;

            if (is_array($default) && isset($default['value'])) {
                $product->setData($attributeCode, $default['value']);
                return $this->handleDefaultValue($product);
            }
        }

        return $product;
    }

    /**
     * Get default warehouse id
     *
     * @return array|mixed|null
     */
    public function getDefaultWarehouseId()
    {
        $warehouses = $this->stockRepository->getAllPointOfSaleAndWarehouse();
        $result = $this->jsonDecoder->decode($warehouses);
        $default = is_array($result) && $result ? array_shift($result) : [];

        $default = isset($default['place_id']) ? $default['place_id'] : null;

        return $default;
    }

    /**
     * Delete value for current store
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $storeId
     * @param null $attributes
     */
    private function cleanGpsValue($product, $storeId, $attributes = null)
    {
        if (!empty($attributes)) {
            $product->getResource()->getConnection()->delete(
                'catalog_product_entity_decimal',
                [
                    'attribute_id IN (?)' => $attributes,
                    'entity_id = ?' => $product->getEntityId(),
                    'store_id <> ?' => $storeId
                ]
            );
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $defaultStoreId
     * @return mixed
     */
    public function processUpdateGpsValue($product, $defaultStoreId)
    {
        if (isset($this->attributeNedUpdate['gps_price_ec'])) {
            $product->addAttributeUpdate(
                'gps_price_ec',
                $product->getData(SapProductInterface::GPS_PRICE),
                $defaultStoreId
            );
        }

        if (isset($this->attributeNedUpdate['future_gps_price_ec'])) {
            $product->addAttributeUpdate(
                'future_gps_price_ec',
                $product->getData(SapProductInterface::FUTURE_GPS_PRICE),
                $defaultStoreId
            );
        }

        if (isset($this->attributeNedUpdate['gps_price'])) {
            $product->addAttributeUpdate(
                'gps_price',
                $product->getData(SapProductInterface::GPS_PRICE),
                $defaultStoreId
            );
        }
        return $product;
    }
}
