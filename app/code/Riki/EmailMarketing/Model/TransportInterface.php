<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\EmailMarketing\Model;

/**
 * Mail Transport interface
 *
 * @api
 */
interface TransportInterface
{
    /**
     * Send a mail using this transport
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage();

    /**
     * @param $stopSend
     * @return mixed
     */
    public function setStopSend($stopSend);
}
