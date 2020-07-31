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

namespace Riki\Session\Model;

use Magento\Framework\Session\Config;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Filesystem;

/**
 * FrontendConfig
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
class FrontendConfig extends Config
{
    /**
     * Configuration path for cookie path
     */
    const XML_PATH_COOKIE_SECURE = 'web/cookie/cookie_secure';

    /**
     * ScopeConfigInterface
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig; // @codingStandardsIgnoreLine

    /**
     * Constructor
     * 
     * @param \Magento\Framework\ValidatorFactory                $validatorFactory ValidatorFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig      ScopeConfigInterface
     * @param \Magento\Framework\Stdlib\StringUtils              $stringHelper     StringUtils
     * @param \Magento\Framework\App\RequestInterface            $request          RequestInterface
     * @param Filesystem                                         $filesystem       Filesystem
     * @param DeploymentConfig                                   $deploymentConfig DeploymentConfig
     * @param string                                             $scopeType        scopeType
     * @param string                                             $lifetimePath     lifetimePath
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __construct(
        \Magento\Framework\ValidatorFactory $validatorFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\StringUtils $stringHelper,
        \Magento\Framework\App\RequestInterface $request,
        Filesystem $filesystem,
        DeploymentConfig $deploymentConfig,
        $scopeType,
        $lifetimePath = self::XML_PATH_COOKIE_LIFETIME
    ) {
        parent::__construct(
            $validatorFactory,
            $scopeConfig,
            $stringHelper,
            $request,
            $filesystem,
            $deploymentConfig,
            $scopeType,
            $lifetimePath
        );

        if ($this->_scopeConfig->getValue(self::XML_PATH_COOKIE_SECURE)) {
            $this->setCookieSecure(true);
        }
    }
}
