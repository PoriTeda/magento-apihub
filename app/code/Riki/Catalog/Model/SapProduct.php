<?php
/**
 * Catalog.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Catalog\Model;

/**
 * SapProduct.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class SapProduct extends \Magento\Framework\DataObject implements \Riki\Catalog\Api\Data\SapProductInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $sku String
     *
     * @return $this
     */
    public function setSku($sku)
    {
        $this->setData(self::SKU, $sku);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name String
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->setData(self::NAME, $name);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getAttributeSetId()
    {
        return $this->getData(self::ATTRIBUTE_SET_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $attributeSetId Int
     *
     * @return $this
     */
    public function setAttributeSetId($attributeSetId)
    {
        $this->setData(self::ATTRIBUTE_SET_ID, $attributeSetId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getPrice()
    {
        return $this->getData(self::PRICE);
    }

    /**
     * {@inheritdoc}
     *
     * @param float $price Float
     *
     * @return $this
     */
    public function setPrice($price)
    {
        $this->setData(self::PRICE, $price);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $status Int
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getVisibility()
    {
        return $this->getData(self::VISIBILITY);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $visibility Int
     *
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->setData(self::VISIBILITY, $visibility);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getTypeId()
    {
        return $this->getData(self::TYPE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $typeId String
     *
     * @return $this
     */
    public function setTypeId($typeId)
    {
        $this->setData(self::TYPE_ID, $typeId);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getWeight()
    {
        return $this->getData(self::WEIGHT);
    }

    /**
     * {@inheritdoc}
     *
     * @param float $weight Float
     *
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->setData(self::WEIGHT, $weight);

        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $qty Int
     *
     * @return $this
     */
    public function setQty($qty)
    {
        $this->setData(self::QTY, $qty);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|null
     */
    public function getIsInStock()
    {
        return $this->getData(self::IS_IN_STOCK);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $isInStock Bool
     *
     * @return $this
     */
    public function setIsInStock($isInStock)
    {
        $this->setData(self::IS_IN_STOCK, $isInStock);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getMaterialType()
    {
        return $this->getData(self::MATERIAL_TYPE);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $materialType String
     *
     * @return $this
     */
    public function setMaterialType($materialType)
    {
        $this->setData(self::MATERIAL_TYPE, $materialType);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $description String
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->setData(self::DESCRIPTION, $description);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getUnitQty()
    {
        return $this->getData(self::UNIT_QTY);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $unitQty Int
     *
     * @return $this
     */
    public function setUnitQty($unitQty)
    {
        $this->setData(self::UNIT_QTY, $unitQty);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getDepth()
    {
        return $this->getData(self::DEPTH);
    }

    /**
     * {@inheritdoc}
     *
     * @param float $depth Float
     *
     * @return $this
     */
    public function setDepth($depth)
    {
        $this->setData(self::DEPTH, $depth);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getWidth()
    {
        return $this->getData(self::WIDTH);
    }

    /**
     * {@inheritdoc}
     *
     * @param float $width Float
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->setData(self::WIDTH, $width);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getHeight()
    {
        return $this->getData(self::HEIGHT);
    }

    /**
     * {@inheritdoc}
     *
     * @param float $height Float
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->setData(self::HEIGHT, $height);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getWeightUnit()
    {
        return $this->getData(self::WEIGHT_UNIT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $weightUnit String
     *
     * @return $this
     */
    public function setWeightUnit($weightUnit)
    {
        $this->setData(self::WEIGHT_UNIT, $weightUnit);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getDimensionUnit()
    {
        return $this->getData(self::DIMENSION_UNIT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $dimensionUnit String
     *
     * @return $this
     */
    public function setDimensionUnit($dimensionUnit)
    {
        $this->setData(self::DIMENSION_UNIT, $dimensionUnit);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShelfLifePeriod()
    {
        return $this->getData(self::SHELF_LIFE_PERIOD);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $shelfLifePeriod String
     *
     * @return $this
     */
    public function setShelfLifePeriod($shelfLifePeriod)
    {
        $this->setData(self::SHELF_LIFE_PERIOD, $shelfLifePeriod);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getPhCode()
    {
        return $this->getData(self::PH_CODE);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $phCode String
     *
     * @return $this
     */
    public function setPhCode($phCode)
    {
        $this->setData(self::PH_CODE, $phCode);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getPh1Description()
    {
        return $this->getData(self::PH1_DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ph1Description String
     *
     * @return $this
     */
    public function setPh1Description($ph1Description)
    {
        $this->setData(self::PH1_DESCRIPTION, $ph1Description);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getPh2Description()
    {
        return $this->getData(self::PH2_DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ph2Description String
     *
     * @return $this
     */
    public function setPh2Description($ph2Description)
    {
        $this->setData(self::PH2_DESCRIPTION, $ph2Description);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getPh3Description()
    {
        return $this->getData(self::PH3_DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ph3Description String
     *
     * @return $this
     */
    public function setPh3Description($ph3Description)
    {
        $this->setData(self::PH3_DESCRIPTION, $ph3Description);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getPh4Description()
    {
        return $this->getData(self::PH4_DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ph4Description String
     *
     * @return $this
     */
    public function setPh4Description($ph4Description)
    {
        $this->setData(self::PH4_DESCRIPTION, $ph4Description);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getPh5Description()
    {
        return $this->getData(self::PH5_DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ph5Description String
     *
     * @return $this
     */
    public function setPh5Description($ph5Description)
    {
        $this->setData(self::PH5_DESCRIPTION, $ph5Description);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getBhSap()
    {
        return $this->getData(self::BH_SAP);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $bhSap String
     *
     * @return $this
     */
    public function setBhSap($bhSap)
    {
        $this->setData(self::BH_SAP, $bhSap);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getUnitSap()
    {
        return $this->getData(self::UNIT_SAP);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $unitSap String
     *
     * @return $this
     */
    public function setUnitSap($unitSap)
    {
        $this->setData(self::UNIT_SAP, $unitSap);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getFutureGpsPrice()
    {
        return $this->getData(self::FUTURE_GPS_PRICE);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $futureGpsPrice String
     *
     * @return $this
     */
    public function setFutureGpsPrice($futureGpsPrice)
    {
        $this->setData(self::FUTURE_GPS_PRICE, $futureGpsPrice);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getFutureGpsPriceFrom()
    {
        return $this->getData(self::FUTURE_GPS_PRICE_FROM);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $futureGpsPriceFrom String
     *
     * @return $this
     */
    public function setFutureGpsPriceFrom($futureGpsPriceFrom)
    {
        $this->setData(self::FUTURE_GPS_PRICE_FROM, $futureGpsPriceFrom);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getGpsPrice()
    {
        return $this->getData(self::GPS_PRICE);
    }

    /**
     * {@inheritdoc}
     *
     * @param float $gpsPrice Float
     *
     * @return $this
     */
    public function setGpsPrice($gpsPrice)
    {
        $this->setData(self::GPS_PRICE, $gpsPrice);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getSalesOrganization()
    {
        return $this->getData(self::SALES_ORGANIZATION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $salesOrganization String
     *
     * @return $this
     */
    public function setSalesOrganization($salesOrganization)
    {
        $this->setData(self::SALES_ORGANIZATION, $salesOrganization);

        return $this;
    }
}
