<?php

namespace Riki\Customer\Model\Indexer\Handler;

use Magento\Framework\Indexer\HandlerInterface;
use Magento\Framework\App\ResourceConnection\SourceProviderInterface;

class ShoshaAttribute implements HandlerInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    protected $eavConfig;
    protected $resource;

    /**
     * ShoshaAttribute constructor.
     *
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Registry $registry
    )
    {
        $this->eavConfig = $eavConfig;
        $this->resource = $resource;
        $this->registry = $registry;
    }

    /**
     * @param SourceProviderInterface $source
     * @param string $alias
     * @param array $fieldInfo
     *
     * @return SourceProviderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareSql(SourceProviderInterface $source, $alias, $fieldInfo)
    {
        $connection = $this->resource->getConnection();

        $attribute = $this->eavConfig->getAttribute('customer', 'shosha_business_code');
        $attributeId = $attribute->getId();

        $source->getSelect()
            ->joinLeft([
                'sbcv' => $connection->getTableName('customer_entity_varchar')],
                sprintf('%s.entity_id = sbcv.entity_id and sbcv.attribute_id=%s', $alias, $attributeId),
                'value'
            )->joinLeft([
                'sbct' => $connection->getTableName('riki_shosha_business_code')],
                'sbcv.value = sbct.shosha_business_code',
                [
                    'shosha_cmp', 'shosha_cmp_kana', 'shosha_code', 'shosha_dept',
                    'shosha_dept_kana', 'shosha_first_code', 'shosha_in_charge',
                    'shosha_in_charge_kana',
                ]
            );

        return $source;
    }
}
