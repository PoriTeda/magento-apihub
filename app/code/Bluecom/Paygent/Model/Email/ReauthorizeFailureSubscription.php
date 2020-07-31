<?php
namespace Bluecom\Paygent\Model\Email;

class ReauthorizeFailureSubscription extends \Bluecom\Paygent\Model\Email\ReauthorizeFailure
{
    const CONFIG_SENDER = 'paygent_config/authorisation/identity';
    const CONFIG_TEMPLATE = 'paygent_config/authorisation/template_subscription';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->template && !parent::getTemplate()) {
            $this->template = 'paygent_config_authorisation_template_subscription';
        }

        return $this->template;
    }
}