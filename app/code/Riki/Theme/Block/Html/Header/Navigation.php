<?php

namespace Riki\Theme\Block\Html\Header;

/**
 * Logo page header block
 *
 * @api
 * @since 100.0.2
 */
class Navigation extends \Riki\Theme\Block\Html\Header\Welcome
{
    protected $httpContext;

    protected $customerProfiles;

    public function __construct
    (
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Riki\Subscription\CustomerData\CustomerProfiles $customerProfiles,
        array $data = []
    )
    {
        parent::__construct($context, $httpContext, $data);
        $this->customerProfiles = $customerProfiles;
    }

    public function getCustomerProfiles(){
        return $this->customerProfiles->getSectionData();
    }
}
