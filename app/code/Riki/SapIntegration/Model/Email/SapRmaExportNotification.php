<?php
namespace Riki\SapIntegration\Model\Email;

class SapRmaExportNotification extends \Riki\Framework\Model\Email\AbstractEmail
{
    const CONFIG_RECEIVER = 'sap_integration_config/export_rma/email_notification';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->template && !parent::getTemplate()) {
            $this->template = 'riki_sapintegration_cron_export_email_template';
        }

        return $this->template;
    }
}