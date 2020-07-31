<?php
/**
 * Directory
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Directory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Directory\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Directory
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Directory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends AbstractHelper
{
    protected $regionFactory;

    protected $regionCodeById = [];

    public function __construct(
        Context $context,
        \Magento\Directory\Model\RegionFactory $regionFactory
    )
    {

        $this->regionFactory = $regionFactory;

        parent::__construct($context);
    }

    /**
     * RoundMethod
     * 
     * @param string $store string
     *
     * @return mixed
     */
    public function getRoundMethod($store = null)
    {
        return $this->scopeConfig->getValue('tax/calculation/round', ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param $regionId
     * @return mixed
     */
    public function getRegionCodeById($regionId)
    {
        if(!isset($this->regionCodeById[$regionId])) {
            $region = $this->regionFactory->create()->load($regionId);

            if ($region) {
                $this->regionCodeById[$regionId] = $region->getCode();
            }

            $this->regionCodeById[$regionId] = '';
        }

        return $this->regionCodeById[$regionId];
    }
}
