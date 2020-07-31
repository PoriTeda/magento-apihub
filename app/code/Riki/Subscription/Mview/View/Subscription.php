<?php
namespace Riki\Subscription\Mview\View;
use Magento\Framework\App\ResourceConnection;

class Subscription extends \Magento\Framework\Mview\View\Subscription
{
    public function __construct(
        ResourceConnection $resource,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Magento\Framework\Mview\View\CollectionInterface $viewCollection,
        \Magento\Framework\Mview\ViewInterface $view,
        $tableName,
        $columnName
    )
    {
        if ($tableName == 'subscription_profile' || $tableName == 'subscription_profile_product_cart') {
            $this->connection = $resource->getConnection('sales');
        } else {
            $this->connection = $resource->getConnection();
        }
        $this->triggerFactory = $triggerFactory;
        $this->viewCollection = $viewCollection;
        $this->view = $view;
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->resource = $resource;
    }

}