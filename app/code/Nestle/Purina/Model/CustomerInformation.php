<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

use Nestle\Purina\Api\CustomerInfoInterface;

/**
 * Class CustomerInformation
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class CustomerInformation implements CustomerInfoInterface
{
    /**
     * The consumerDbId for customer id.
     *
     * @var int
     */
    protected $consumerDbId;

    /**
     * Gets the consumerDbId.
     *
     * @return int
     */
    public function getConsumerDbId()
    {
        return $this->consumerDbId;
    }

    /**
     * Sets the consumerDbId.
     *
     * @param string $consumerDbId consumer_id
     *
     * @return $this
     */
    public function setConsumerDbId($consumerDbId)
    {
        $this->consumerDbId = $consumerDbId;
    }
}
