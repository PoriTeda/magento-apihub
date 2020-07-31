<?php

namespace Riki\Customer\Block\Plugin;

class CustomerDataSetting
{
    protected $_pageReloadingHelper;

    public function __construct(\Riki\Session\Helper\PageReloading $pageReloadingHelper)
    {
        $this->_pageReloadingHelper = $pageReloadingHelper;
    }

    /**
     * Default interval will be used instead of cookie lifetime in case cookie lifetime equal zero
     *
     * @param $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterGetCookieLifeTime($subject, $result)
    {
        if (!$result) {
            $result = $this->_pageReloadingHelper->getDefaultInterval();
        }
        return $result;
    }
}
