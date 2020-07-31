<?php
namespace Riki\Chirashi\Model\CustomerSegment\Segment\Condition\Product;

class Attributes extends \Magento\CustomerSegment\Model\Segment\Condition\Product\Attributes
{
    /**
     * Apply product attribute subfilter to parent/base condition query
     *
     * @param string $fieldName base query field name
     * @param bool $requireValid strict validation flag
     * @param int|Zend_Db_Expr $website
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return string
     */
    public function getSubfilterSql($fieldName, $requireValid, $website)
    {
        $attribute = $this->getAttributeObject();
        $table = $attribute->getBackendTable();

        $resource = $this->getResource();
        $select = $resource->createSelect();
        $select->from(['main' => $table], ['entity_id']);

        if ($attribute->getAttributeCode() == 'category_ids') {
            $condition = $resource->createConditionSql(
                'cat.category_id',
                $this->getOperator(),
                $this->getValueParsed()
            );
            $categorySelect = $resource->createSelect();
            $categorySelect->from(
                ['cat' => $resource->getTable('catalog_category_product')],
                'product_id'
            )->where(
                $condition
            );
            $condition = 'main.entity_id IN (' . $categorySelect . ')';
        } elseif ($attribute->isStatic()) {
            $condition = $this->getResource()->createConditionSql(
                "main.{$attribute->getAttributeCode()}",
                $this->getOperator(),
                $this->getValue()
            );
        }elseif ($attribute->getAttributeCode() == 'chirashi') {
            $select->where('main.attribute_id = ?', $attribute->getId());
            $condition = $this->getResource()->createConditionSql(
                'main.value',
                $this->getOperator(),
                $this->getValue()
            );
        } else {
            $select->where('main.attribute_id = ?', $attribute->getId());
            $select->join(
                ['store' => $this->getResource()->getTable('store')],
                'main.store_id=store.store_id',
                []
            )->where(
                'store.website_id IN(?)',
                [0, $website]
            );
            $condition = $this->getResource()->createConditionSql(
                'main.value',
                $this->getOperator(),
                $this->getValue()
            );
        }
        $select->where($condition);
        $inOperator = $requireValid ? 'IN' : 'NOT IN';
        if ($this->getCombineProductCondition()) {
            // when used as a child of History or List condition - "IN" always set to "IN"
            $inOperator = 'IN';
        }

        $productIds = $this->getData('product_ids');

        if ($productIds) {
            $select->where('main.entity_id IN(?)', $productIds);
        }

        $entityIds = implode(',', $this->getResource()->getConnection()->fetchCol($select));
        if (empty($entityIds)) {
            return $requireValid ? "FALSE" : "TRUE";
        }
        return sprintf("%s %s (%s)", $fieldName, $inOperator, $entityIds);
    }
}
