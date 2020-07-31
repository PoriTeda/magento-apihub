<?php
namespace Riki\Subscription\Model\Profile\ResourceModel;

use Riki\Subscription\Model\Version\VersionFactory;

/**
 * Class Profile
 * @package Riki\Subscription\Model\Profile\ResourceModel
 */
class Profile extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var VersionFactory
     */
    protected $versionFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * @var \Riki\Subscription\Logger\LoggerReplaceProduct
     */
    protected $logger;
    /**
     * Profile constructor .
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param VersionFactory $versionFactory
     * @param \Riki\Subscription\Logger\LoggerReplaceProduct $logger
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        VersionFactory $versionFactory,
        \Riki\Subscription\Logger\LoggerReplaceProduct $logger,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        $connectionName = null
    ) {
        $this->productFactory = $productFactory;
        $this->logger = $logger;
        $this->versionFactory = $versionFactory;
        parent::__construct($context, $connectionName);
    }
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_profile', 'profile_id');
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Model\Product
     */
    public function getProductById($id)
    {
        $productModel = $this->productFactory->create();
        $product = $productModel->load($id);
        return $product;
    }
}
