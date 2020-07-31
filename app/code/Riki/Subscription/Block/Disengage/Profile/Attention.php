<?php

namespace Riki\Subscription\Block\Disengage\Profile;

use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;

/**
 * Class Attention
 * @package Riki\Subscription\Block\Disengage\Profile
 */

class Attention extends AbstractDisengagement
{
     /**
      * @return string
      */
    public function getProfileListUrl()
    {
        return $this->getUrl(DisengagementUrl::URL_DISENGAGEMENT_LIST);
    }

    /**
     * Get session variable to verify all attentions were checked or not
     * @return mixed
     */
    public function getCheckedAttentions()
    {
        return $this->_session->getAttentionNote();
    }
}
