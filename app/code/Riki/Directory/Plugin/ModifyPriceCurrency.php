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
class ModifyPriceCurrency
{
    /**
     * ScopeConfigInterface
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * Helper
     *
     * @var \Riki\Directory\Helper\Data
     */
    protected $helperDirectory;

    /**
     * ModifyPrice constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context Context
     * @param \Riki\Directory\Helper\Data             $helper  Helper
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Riki\Directory\Helper\Data $helper
    ) {
    
        $this->scopeConfigInterface = $context->getScopeConfig();
        $this->helperDirectory = $helper;
    }

    /**
     * Format
     *
     * @param \Magento\Directory\Model\PriceCurrency $subject          PriceCurrency
     * @param \Closure                               $proceed          Closure
     * @param string                                 $amount           amount
     * @param bool                                   $includeContainer includeContainer
     * @param int                                    $precision        precision
     * @param null                                   $scope            scope
     * @param null                                   $currency         currency
     *
     * @return mixed
     */
    public function aroundFormat(
        \Magento\Directory\Model\PriceCurrency $subject,
        \Closure $proceed,
        $amount,
        $includeContainer = true,
        $precision = \Magento\Directory\Model\PriceCurrency::DEFAULT_PRECISION,
        $scope = null,
        $currency = null
    ) {
    
        if ($subject->getCurrency()->getCode() == 'JPY') {
            $precision = '0';
            return $subject->getCurrency($scope, $currency)
                ->formatPrecision($amount, $precision, [], $includeContainer);
        }
        return $proceed($amount, $includeContainer, $precision, $scope, $currency);
    }

    /**
     * ConvertAndRound
     *
     * @param \Magento\Directory\Model\PriceCurrency $subject   PriceCurrency
     * @param \Closure                               $proceed   Closure
     * @param string                                 $amount    amount
     * @param null                                   $scope     scope
     * @param null                                   $currency  currency
     * @param int                                    $precision precision
     *
     * @return mixed
     */
    public function aroundConvertAndRound(
        \Magento\Directory\Model\PriceCurrency $subject,
        \Closure $proceed,
        $amount,
        $scope = null,
        $currency = null,
        $precision = \Magento\Directory\Model\PriceCurrency::DEFAULT_PRECISION
    ) {
    
        if ($subject->getCurrency()->getCode() == 'JPY') {
            $method = $this->helperDirectory->getRoundMethod($scope);

            if ($method != 'round') {
                return $method($amount);
            }
        }
        return $proceed($amount, $scope, $currency, $precision);
    }


    /**
     * Round
     *
     * @param \Magento\Directory\Model\PriceCurrency $subject   PriceCurrency
     * @param \Closure                               $proceed   Closure
     * @param string                                 $amount    amount
     * @param int                                    $precision precision
     *
     * @return mixed
     */
    public function aroundRound(
        \Magento\Directory\Model\PriceCurrency $subject,
        \Closure $proceed,
        $amount,
        $precision = 2
    ) {
    
        if ($subject->getCurrency()->getCode() == 'JPY') {
            $method = $this->helperDirectory->getRoundMethod();
            if ($method != 'round') {
                return $method($amount);
            }
        }
        return $proceed($amount, $precision);
    }
}
