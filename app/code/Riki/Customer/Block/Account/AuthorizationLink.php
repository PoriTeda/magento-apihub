<?php

namespace Riki\Customer\Block\Account;

/**
 * Customer authorization link.
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class AuthorizationLink extends \Magento\Customer\Block\Account\AuthorizationLink
{
    protected $ssoConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Riki\Customer\Model\SsoConfig $ssoConfig,
        array $data = []
    ) {
        $this->ssoConfig = $ssoConfig;

        parent::__construct($context, $httpContext, $customerUrl, $postDataHelper, $data);
    }

    public function getPostParamsForUrl($url, $checkSSO = false)
    {
        if (!$checkSSO || ($checkSSO && $this->ssoConfig->isEnabled())) {
            return sprintf(" data-post='%s'", $this->_postDataHelper->getPostData($url));
        }

        return '';
    }

    public function getUserUrl()
    {
        return $this->_customerUrl;
    }
}
