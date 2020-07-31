<?php
/**
 * Session
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Session
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Session\Helper;

use Magento\Store\Model\ScopeInterface;

/**
 * PageReloading
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Session
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class PageReloading extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_PAGE_RELOADING_DEFAULT_INTERVAL = 'web/page_reloading/default_interval';
    const XML_PATH_PAGE_RELOADING_EXTRA_INTERVAL   = 'web/page_reloading/extra_interval';

    /**
     * Get default interval
     *
     * @return mixed
     */
    public function getDefaultInterval()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAGE_RELOADING_DEFAULT_INTERVAL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get extra interval
     *
     * @return mixed
     */
    public function getExtraInterval()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAGE_RELOADING_EXTRA_INTERVAL,
            ScopeInterface::SCOPE_STORE
        );
    }
}