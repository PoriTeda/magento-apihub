<?php
namespace Riki\Subscription\Mview\View;
use Magento\Framework\App\ResourceConnection;

class Changelog extends \Magento\Framework\Mview\View\Changelog
{
    public function setConnection()
    {
        if ($this->getName() == 'profile_simulator_cl' || $this->getName() == 'subscription_profile_product_cart') {
            $this->connection = $this->resource->getConnection('sales');
        }
    }

    /**
     * Create changelog table
     *
     * @return void
     * @throws \Exception
     */
    public function create()
    {
        $this->setConnection();
        parent::create();
    }

    /**
     * Drop changelog table
     *
     * @return void
     * @throws \Exception
     */
    public function drop()
    {
        $this->setConnection();
        parent::drop();
    }

    /**
     * Clear changelog table by version_id
     *
     * @param int $versionId
     * @return boolean
     * @throws \Exception
     */
    public function clear($versionId)
    {
        $this->setConnection();
        return parent::clear($versionId);
    }

    /**
     * Retrieve entity ids by range [$fromVersionId..$toVersionId]
     *
     * @param int $fromVersionId
     * @param int $toVersionId
     * @return int[]
     * @throws \Exception
     */
    public function getList($fromVersionId, $toVersionId)
    {
        $this->setConnection();
        return parent::getList($fromVersionId, $toVersionId);
    }

    /**
     * Get maximum version_id from changelog
     *
     * @return int
     * @throws \Exception
     */
    public function getVersion()
    {
        $this->setConnection();
        return parent::getVersion();
    }
}