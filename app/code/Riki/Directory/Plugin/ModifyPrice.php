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

namespace Riki\Directory\Plugin;

use \Magento\Framework\Locale\Format;

/**
 * ModifyPrice
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
class ModifyPrice
{
    /**
     * ScopeResolverInterface
     *
     * @var \Magento\Framework\App\ScopeResolverInterface
     */
    protected $scopeResolverInterface;

    /**
     * CurrencyFactory
     *
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\ScopeResolverInterface $scopeResolver   ScopeResolverInterface
     * @param \Magento\Directory\Model\CurrencyFactory      $currencyFactory CurrencyFactory
     */
    public function __construct(
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
    ) {
        $this->scopeResolverInterface = $scopeResolver;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * Get price format
     *
     * @param Format   $subject      Format
     * @param \Closure $proceed      Closure
     * @param string   $localeCode   string
     * @param string   $currencyCode string
     *
     * @return mixed
     */
    public function aroundGetPriceFormat(
        Format $subject,
        \Closure $proceed,
        $localeCode = null,
        $currencyCode = null
    ) {
        if ($currencyCode) {
            $currency = $this->currencyFactory->create()->load($currencyCode);
        } else {
            $currency = $this->scopeResolverInterface->getScope()->getCurrentCurrency();
        }

        $result = $proceed($localeCode, $currencyCode);

        if ($currency->getCode() == 'JPY') {
            $result['precision'] = '0';
            $result['requiredPrecision'] = '0';
        }

        return $result;
    }
}
