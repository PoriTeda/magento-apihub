<?php
namespace Riki\Subscription\Model\Profile\WebApi;

class ProfileResults
{
    protected $_returnMessage;

    /**
     * Get return message
     *
     * @return string
     */
    public function getReturnMessage()
    {
        return $this->_returnMessage;
    }

    /**
     * Set return message
     *
     * @param int $message
     * @return $this
     */
    public function setReturnMessage($message)
    {
        $this->_returnMessage = $message;
        return $this;
    }
}