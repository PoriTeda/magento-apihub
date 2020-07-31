<?php

namespace Riki\Subscription\Block\Disengage\Profile;

use Riki\Subscription\Model\Config\Source\Profile\DisengagementUrl;
use Riki\SubscriptionProfileDisengagement\Model\Reason;

/**
 * Class Questionnaire
 * @package Riki\Subscription\Block\Disengage\Profile
 */

class Questionnaire extends AbstractDisengagement
{
    /**
     * @return mixed
     */
    public function getDisengagementReason()
    {
        $visibilities = [Reason::VISIBILITY_FRONTEND, Reason::VISIBILITY_BOTH];
        return $this->reasonModel->getDisengagementReasons([], $visibilities);
    }

    /**
     * @return string
     */
    public function getAttentionUrl()
    {
        return $this->getUrl(DisengagementUrl::URL_DISENGAGEMENT_ATTENTION);
    }

    /**
     * @return array
     */
    public function getSelectedReasons()
    {
        return $this->_session->getSelectedReasons() ? $this->_session->getSelectedReasons() : [];
    }

    /**
     * @return array
     */
    public function getSelectedQuestionnaireAnswers()
    {
        return $this->_session->getSelectedQuestionnaireAnswers() ? $this->_session->getSelectedQuestionnaireAnswers() : [];
    }
}
