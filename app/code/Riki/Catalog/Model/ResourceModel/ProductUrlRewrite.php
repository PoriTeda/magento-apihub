<?php
namespace Riki\Catalog\Model\ResourceModel;

use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;

class ProductUrlRewrite
{

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @param Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        Config $eavConfig,
        ResourceConnection $resource
    ) {
        $this->eavConfig = $eavConfig;
        $this->connection = $resource->getConnection();
    }

    /**
     * Retrieve entity overridden url key for specific store
     *
     * @param int $storeId
     * @param int $entityId
     * @param string $entityType
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getEntityOverriddenUrlKeyForStore($storeId, $entityId, $entityType)
    {
        $attribute = $this->eavConfig->getAttribute($entityType, 'url_key');
        if (!$attribute) {
            throw new \InvalidArgumentException(sprintf('Cannot retrieve attribute for entity type "%s"', $entityType));
        }

        $select = $this->connection->select()
            ->from($attribute->getBackendTable(), 'value')
            ->where('attribute_id = ?', $attribute->getId())
            ->where('entity_id = ?', $entityId)
            ->where('store_id = ?', $storeId);

        return $this->connection->fetchOne($select);
    }


}