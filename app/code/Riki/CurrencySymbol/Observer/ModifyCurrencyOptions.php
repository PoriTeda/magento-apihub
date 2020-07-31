<?php
/**
 * CurrencySymbol
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CurrencySymbol
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CurrencySymbol\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * ModifyCurrencyOptions
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CurrencySymbol
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ModifyCurrencyOptions implements ObserverInterface
{
    /**
     * CurrencysymbolFactory
     *
     * @var \Magento\CurrencySymbol\Model\System\CurrencysymbolFactory
     */
    protected $symbolFactory;

    /**
     * Constructor
     *
     * @param \Magento\CurrencySymbol\Model\System\CurrencysymbolFactory $symbolFactory CurrencysymbolFactory
     */
    public function __construct(
        \Magento\CurrencySymbol\Model\System\CurrencysymbolFactory $symbolFactory
    ) {
        $this->symbolFactory = $symbolFactory;
    }

    /**
     * Generate options for currency displaying with custom currency symbol
     *
     * @param \Magento\Framework\Event\Observer $observer Observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $baseCode = $observer->getEvent()->getBaseCode();
        $currencyOptions = $observer->getEvent()->getCurrencyOptions();
        $originalOptions = $currencyOptions->getData();
        $currencyOptions->setData($this->getCurrencyOptions($baseCode, $originalOptions));

        return $this;
    }

    /**
     * Get currency display options
     *
     * @param string $baseCode        string
     * @param array  $originalOptions array
     *
     * @return array
     */
    protected function getCurrencyOptions($baseCode, $originalOptions)
    {
        $currencyOptions = [];
        if ($baseCode == 'JPY') {
            $currencyOptions['precision'] = '0';
        }
        return array_merge($originalOptions, $currencyOptions);
    }
}
