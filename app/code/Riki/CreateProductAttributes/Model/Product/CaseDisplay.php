<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Product visibilite model and attribute source model
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Riki\CreateProductAttributes\Model\Product;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\DB\Ddl\Table;

class CaseDisplay extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const CD_PIECE_ONLY = 1;

    const CD_CASE_ONLY = 2;

    const CD_PIECE_AND_CASE = 3;

    const PROFILE_UNIT_PIECE = 'EA';
    const PROFILE_UNIT_CASE = 'CS';

    const PRODUCT_LISTING_PAGE = 'listing';

    /**
     * Reference to the attribute instance
     *
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $_attribute;

    /**
     * Eav entity attribute
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavEntityAttribute;

    /**
     * Construct
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavEntityAttribute
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavEntityAttribute,
        array $data = []
    ) {
        $this->_eavEntityAttribute = $eavEntityAttribute;
        parent::__construct($data);
    }


    /**
     * Retrieve option array
     *
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::CD_PIECE_ONLY => __('Only Piece'),
            self::CD_CASE_ONLY => __('Only Case'),
            self::CD_PIECE_AND_CASE => __('Piece And Case')
        ];
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOption()
    {
        $options = self::getOptionArray();
        array_unshift($options, ['value' => '', 'label' => '']);
        return $options;
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOptions()
    {
        $res = [];
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * Retrieve option text
     *
     * @param int $optionId
     * @return string
     */
    public static function getOptionText($optionId)
    {
        $options = self::getOptionArray();
        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();

        return [
            $attributeCode => [
                'unsigned' => true,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_SMALLINT,
                'nullable' => true,
                'comment' => 'Case Display ' . $attributeCode . ' column',
            ],
        ];
    }

    /**
     * Retrieve Indexes for Flat
     *
     * @return array
     */
    public function getFlatIndexes()
    {
        return [];
    }

    /**
     * Retrieve Select For Flat Attribute update
     *
     * @param int $store
     * @return \Magento\Framework\DB\Select|null
     */
    public function getFlatUpdateSelect($store)
    {
        return $this->_eavEntityAttribute->getFlatUpdateSelect($this->getAttribute(), $store);
    }

    /**
     * Set attribute instance
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @return $this
     */
    public function setAttribute($attribute)
    {
        $this->_attribute = $attribute;
        return $this;
    }

    /**
     * Get attribute instance
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    public function getAttribute()
    {
        return $this->_attribute;
    }

    /**
     * Add Value Sort To Collection Select
     *
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     * @param string $dir direction
     * @return $this
     */
    public function addValueSortToCollection($collection, $dir = 'asc')
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $attributeId = $this->getAttribute()->getId();
        $attributeTable = $this->getAttribute()->getBackend()->getTable();

        if ($this->getAttribute()->isScopeGlobal()) {
            $tableName = $attributeCode . '_t';
            $collection->getSelect()->joinLeft(
                [$tableName => $attributeTable],
                "e.entity_id={$tableName}.entity_id" .
                " AND {$tableName}.attribute_id='{$attributeId}'" .
                " AND {$tableName}.store_id='0'",
                []
            );
            $valueExpr = $tableName . '.value';
        } else {
            $valueTable1 = $attributeCode . '_t1';
            $valueTable2 = $attributeCode . '_t2';
            $collection->getSelect()->joinLeft(
                [$valueTable1 => $attributeTable],
                "e.entity_id={$valueTable1}.entity_id" .
                " AND {$valueTable1}.attribute_id='{$attributeId}'" .
                " AND {$valueTable1}.store_id='0'",
                []
            )->joinLeft(
                [$valueTable2 => $attributeTable],
                "e.entity_id={$valueTable2}.entity_id" .
                " AND {$valueTable2}.attribute_id='{$attributeId}'" .
                " AND {$valueTable2}.store_id='{$collection->getStoreId()}'",
                []
            );
            $valueExpr = $collection->getConnection()->getCheckSql(
                $valueTable2 . '.value_id > 0',
                $valueTable2 . '.value',
                $valueTable1 . '.value'
            );
        }

        $collection->getSelect()->order($valueExpr . ' ' . $dir);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    /**
     * Get CaseDisplay Key to saving into Subscription Profile Product Cart
     *
     * @param int $display
     *
     * @return string $unitCase EA, CS
     */
    public function getCaseDisplayKey($display)
    {
        $unitCase = self::PROFILE_UNIT_PIECE;

        $data = [
            self::CD_PIECE_ONLY => self::PROFILE_UNIT_PIECE,
            self::CD_CASE_ONLY => self::PROFILE_UNIT_CASE,
            self::CD_PIECE_AND_CASE => self::PROFILE_UNIT_PIECE // removed this option - default value is Piece
        ];

        if (array_key_exists($display, $data)) {
            $unitCase = $data[$display];
        }

        return $unitCase;
    }

    /**
     * Get Case Display Text translation for display
     *
     * @param int $case
     *
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getCaseDisplayText($case)
    {
        $unitCase = __('Piece');
        $data = [
            self::CD_PIECE_ONLY => __('Piece'),
            self::CD_CASE_ONLY => __('Case')
        ];

        if (array_key_exists($case, $data)) {
            $unitCase = $data[$case];
        }

        return $unitCase;
    }

    /**
     * GetQtyPieceCaseForSaving
     *
     * @param int $display
     * @param int $unitQty
     * @param int $productQty
     *
     * @return int $unitQty
     */
    public function getQtyPieceCaseForSaving($display, $unitQty, $productQty)
    {
        $unitQty = $this->validateQtyPieceCase($display, $unitQty);

        return $unitQty * $productQty;
    }

    /**
     * GetQtyPieceCaseForDisplay
     *
     * @param int $unitQty
     * @param int $productQty
     * @param int|string $case
     * @param bool $isProfile
     *
     * @return int $unitQty
     */
    public function getQtyPieceCaseForDisplay($unitQty, $productQty, $case, $isProfile = true)
    {
        if ($isProfile) {
            $isPiece = $case == self::PROFILE_UNIT_PIECE;
        } else {
            $isPiece = $case == self::CD_PIECE_ONLY || $case == self::CD_PIECE_AND_CASE;
        }

        if ($isPiece) {
            return $productQty;
        }
        if ($productQty < 1) {
            return $unitQty;
        }
        if (!$unitQty || $unitQty < 1) {
            return $productQty;
        }
        return $productQty / $unitQty;
    }

    /**
     * Validate the unit_qty
     *
     * @param int $display
     * @param int $unitQty
     *
     * @return int
     */
    public function validateQtyPieceCase($display, $unitQty)
    {
        $unit = $this->getCaseDisplayKey($display);
        if ($unit == self::PROFILE_UNIT_PIECE) {
            $unitQty = 1;
        }
        if ($unitQty == NULL || !is_numeric($unitQty) || $unitQty < 1) {
            $unitQty = 1;
        }
        return $unitQty;
    }
}
