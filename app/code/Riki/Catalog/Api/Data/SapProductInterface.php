<?php
namespace Riki\Catalog\Api\Data;

interface SapProductInterface
{
    /**#@+
     * Constants defined for keys of  data array
     */
    const SKU = 'sku';
    const NAME = 'name';
    const ATTRIBUTE_SET_ID = 'attribute_set_id';
    const PRICE = 'price';
    const STATUS = 'status';
    const VISIBILITY = 'visibility';
    const TYPE_ID = 'type_id';
    const WEIGHT = 'weight';

    const QTY = 'qty';
    const IS_IN_STOCK = 'is_in_stock';

    const MATERIAL_TYPE = 'material_type';
    const DESCRIPTION = 'description';
    const UNIT_QTY = 'unit_qty';
    const DEPTH = 'depth';
    const WIDTH = 'width';
    const HEIGHT = 'height';
    const WEIGHT_UNIT = 'weight_unit';
    const DIMENSION_UNIT = 'dimension_unit';
    const SHELF_LIFE_PERIOD = 'shelf_life_period';
    const PH_CODE = 'ph_code';
    const PH1_DESCRIPTION = 'ph1_description';
    const PH2_DESCRIPTION = 'ph2_description';
    const PH3_DESCRIPTION = 'ph3_description';
    const PH4_DESCRIPTION = 'ph4_description';
    const PH5_DESCRIPTION = 'ph5_description';
    const BH_SAP = 'bh_sap';
    const UNIT_SAP = 'unit_sap';
    const FUTURE_GPS_PRICE = 'future_gps_price';
    const FUTURE_GPS_PRICE_FROM = 'future_gps_price_from';
    const GPS_PRICE = 'gps_price';
    const SALES_ORGANIZATION = 'sales_organization';
    const FUTURE_GPS_PRICE_EC = 'future_gps_price_ec';

    /**
     * Product sku
     *
     * @return string
     */
    public function getSku();

    /**
     * Set product sku
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * Product name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set product name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Product attribute set id
     *
     * @return int|null
     */
    public function getAttributeSetId();

    /**
     * Set product attribute set id
     *
     * @param int $attributeSetId
     * @return $this
     */
    public function setAttributeSetId($attributeSetId);

    /**
     * Product price
     *
     * @return float|null
     */
    public function getPrice();

    /**
     * Set product price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice($price);

    /**
     * Product status
     *
     * @return int|null
     */
    public function getStatus();

    /**
     * Set product status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Product visibility
     *
     * @return int|null
     */
    public function getVisibility();

    /**
     * Set product visibility
     *
     * @param int $visibility
     * @return $this
     */
    public function setVisibility($visibility);

    /**
     * Product type id
     *
     * @return string|null
     */
    public function getTypeId();

    /**
     * Set product type id
     *
     * @param string $typeId
     * @return $this
     */
    public function setTypeId($typeId);

    /**
     * Product weight
     *
     * @return float|null
     */
    public function getWeight();

    /**
     * Set product weight
     *
     * @param float $weight
     * @return $this
     */
    public function setWeight($weight);

    /**
     * Product qty
     *
     * @return int|null
     */
    public function getQty();

    /**
     * Set product qty
     *
     * @param int $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * Product is in stock
     *
     * @return bool|null
     */
    public function getIsInStock();

    /**
     * Set product is in stock
     *
     * @param bool $isInStock
     * @return $this
     */
    public function setIsInStock($isInStock);

    /**
     * Product material type (custom_attribute)
     *
     * @return string|null
     */
    public function getMaterialType();

    /**
     * Set product material type (custom_attribute)
     *
     * @param string $materialType
     * @return $this
     */
    public function setMaterialType($materialType);

    /**
     * Product description (custom_attribute)
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set product description (custom_attribute)
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Product unit quantity (custom_attribute)
     *
     * @return int|null
     */
    public function getUnitQty();

    /**
     * Set product unit quantity (custom_attribute)
     *int
     * @param int $unitQty
     * @return $this
     */
    public function setUnitQty($unitQty);

    /**
     * Product depth (custom_attribute)
     *
     * @return float|null
     */
    public function getDepth();

    /**
     * Set product depth (custom_attribute)
     *
     * @param float $depth
     * @return $this
     */
    public function setDepth($depth);

    /**
     * Product width (custom_attribute)
     *
     * @return float|null
     */
    public function getWidth();

    /**
     * Set product width (custom_attribute)
     *
     * @param float $width
     * @return $this
     */
    public function setWidth($width);

    /**
     * Product height (custom_attribute)
     *
     * @return float|null
     */
    public function getHeight();

    /**
     * Set product height (custom_attribute)
     *
     * @param float $height
     * @return $this
     */
    public function setHeight($height);

    /**
     * Product weight unit (custom_attribute)
     *
     * @return string|null
     */
    public function getWeightUnit();

    /**
     * Set product weight unit (custom_attribute)
     *
     * @param string $weightUnit
     * @return $this
     */
    public function setWeightUnit($weightUnit);

    /**
     * Product dimension unit (custom_attribute)
     *
     * @return string|null
     */
    public function getDimensionUnit();

    /**
     * Set product dimension unit (custom_attribute)
     *
     * @param string $dimensionUnit
     * @return $this
     */
    public function setDimensionUnit($dimensionUnit);

    /**
     * Product shelf life period (custom_attribute)
     *
     * @return string|null
     */
    public function getShelfLifePeriod();

    /**
     * Set shelf life period (custom_attribute)
     *
     * @param string $shelfLifePeriod
     * @return $this
     */
    public function setShelfLifePeriod($shelfLifePeriod);

    /**
     * Product ph code (custom_attribute)
     *
     * @return string|null
     */
    public function getPhCode();

    /**
     * Set product ph code (custom_attribute)
     *
     * @param string $phCode
     * @return $this
     */
    public function setPhCode($phCode);

    /**
     * Product ph1 description (custom_attribute)
     *
     * @return string|null
     */
    public function getPh1Description();

    /**
     * Set product ph1 description (custom_attribute)
     *
     * @param string $ph1Description
     * @return $this
     */
    public function setPh1Description($ph1Description);

    /**
     * Product ph2 description (custom_attribute)
     *
     * @return string|null
     */
    public function getPh2Description();

    /**
     * Set product ph2 description (custom_attribute)
     *
     * @param string $ph2Description
     * @return $this
     */
    public function setPh2Description($ph2Description);

    /**
     * Product ph3 description (custom_attribute)
     *
     * @return string|null
     */
    public function getPh3Description();

    /**
     * Set product ph3 description (custom_attribute)
     *
     * @param string $ph3Description
     * @return $this
     */
    public function setPh3Description($ph3Description);

    /**
     * Product ph4 description (custom_attribute)
     *
     * @return string|null
     */
    public function getPh4Description();

    /**
     * Set product ph4 description (custom_attribute)
     *
     * @param string $ph4Description
     * @return $this
     */
    public function setPh4Description($ph4Description);

    /**
     * Product ph5 description (custom_attribute)
     *
     * @return string|null
     */
    public function getPh5Description();

    /**
     * Set product ph5 description (custom_attribute)
     *
     * @param string $ph5Description
     * @return $this
     */
    public function setPh5Description($ph5Description);

    /**
     * Product bh sap (custom_attribute)
     *
     * @return string|null
     */
    public function getBhSap();

    /**
     * Set product bh sap (custom_attribute)
     *
     * @param string $bhSap
     * @return $this
     */
    public function setBhSap($bhSap);

    /**
     * Product unit sap (custom_attribute)
     *
     * @return string|null
     */
    public function getUnitSap();

    /**
     * Set product unit sap (custom_attribute)
     *
     * @param string $unitSap
     * @return $this
     */
    public function setUnitSap($unitSap);

    /**
     * Product future gps price (custom_attribute)
     *
     * @return float|null
     */
    public function getFutureGpsPrice();

    /**
     * Set product future gps price (custom_attribute)
     *
     * @param string $futureGpsPrice
     * @return $this
     */
    public function setFutureGpsPrice($futureGpsPrice);

    /**
     * Product future gps price from (custom_attribute)
     *
     * @return string|null
     */
    public function getFutureGpsPriceFrom();

    /**
     * Set product future gps price from (custom_attribute)
     *
     * @param string $futureGpsPriceFrom
     * @return $this
     */
    public function setFutureGpsPriceFrom($futureGpsPriceFrom);

    /**
     * Product gps price (custom_attribute)
     *
     * @return float|null
     */
    public function getGpsPrice();

    /**
     * Set product gps price (custom_attribute)
     *
     * @param float $gpsPrice
     * @return $this
     */
    public function setGpsPrice($gpsPrice);

    /**
     * Product sales organization (custom_attribute)
     *
     * @return string|null
     */
    public function getSalesOrganization();

    /**
     * Set product sales organization (custom_attribute)
     *
     * @param string $salesOrganization
     * @return $this
     */
    public function setSalesOrganization($salesOrganization);
}
