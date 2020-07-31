<?php
namespace Riki\ThirdPartyImportExport\Block\View\Element\Html\Link;


class LegacyOrders extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * LegacyOrders constructor.
     * @param \Riki\ThirdPartyImportExport\Helper\Order\Config $config
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param array $data
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\Order\Config $config,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        array $data = []
    )
    {
        $localeDate = $context->getLocaleDate();
        $data['label'] = __('Order History Before %1', $localeDate->formatDate($config->getCommonAnchor_date()))->render();

        parent::__construct($context, $defaultPath, $data);
    }

}