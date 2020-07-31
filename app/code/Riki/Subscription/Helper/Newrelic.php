<?php
namespace Riki\Subscription\Helper;


class Newrelic
{
    public function startTransaction()
    {
        if ($this->isNewRelicEnabled()) {
            newrelic_start_transaction(ini_get('newrelic.appname'));
        }
    }
    public function ignoreTransaction()
    {
        if ($this->isNewRelicEnabled()) {
            newrelic_ignore_transaction();
        }
    }
    public function endTransaction()
    {
        if ($this->isNewRelicEnabled()) {
            newrelic_end_transaction();
        }
    }

    /**
     * Checks whether the NewRelic extension is enabled in the system.
     *
     * @return bool
     */
    protected function isNewRelicEnabled()
    {
        return extension_loaded('newrelic');
    }

    /**
     * Overwrites the name of the current transaction
     *
     * @param string $transactionName
     */
    public function setNewRelicTransactionName($transactionName)
    {
        if ($this->isNewRelicEnabled()) {
            newrelic_name_transaction($transactionName);
        }
    }

    /**
     * Wrapper for 'newrelic_add_custom_parameter' function
     *
     * @param string $param
     * @param string|int $value
     * @return bool
     */
    public function addCustomParameter($param, $value)
    {
        if ($this->isNewRelicEnabled()) {
            newrelic_add_custom_parameter($param, $value);
            return true;
        }
        return false;
    }
}