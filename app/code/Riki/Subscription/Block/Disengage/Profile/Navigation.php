<?php

namespace Riki\Subscription\Block\Disengage\Profile;

/**
 * Class Navigation
 * @package Riki\Subscription\Block\Disengage\Profile
 */

class Navigation extends \Magento\Framework\View\Element\Template
{
    const NAVIGATION_TAB_LIST = 'list';
    const NAVIGATION_TAB_ATTENTION = 'attention';
    const NAVIGATION_TAB_QUESTIONNAIRE = 'questionnaire';
    /**
     * @return array
     */
    public function getActiveTabs()
    {
        $activeTabs = [
            'active_list' => '',
            'active_attention' => '',
            'active_questionnaire' => ''
        ];
        switch ($this->_request->getActionName()) {
            case self::NAVIGATION_TAB_ATTENTION:
                $activeTabs['active_list'] = 'active';
                $activeTabs['active_attention'] = 'active';
                break;
            case self::NAVIGATION_TAB_QUESTIONNAIRE:
                $activeTabs['active_questionnaire'] = 'active';
                $activeTabs['active_attention'] = 'active';
                $activeTabs['active_list'] = 'active';
                break;
            default:
                $activeTabs['active_list'] = 'active';
                break;
        }
        return $activeTabs;
    }
}
