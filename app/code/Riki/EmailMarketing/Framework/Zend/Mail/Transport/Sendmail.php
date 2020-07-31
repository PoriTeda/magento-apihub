<?php
/**
 * Workaround to fix object manager issue: zend framework 1 optional parameter will get ObjectManager object
 * when compilation is enabled.
 */

namespace Riki\EmailMarketing\Framework\Zend\Mail\Transport;

class Sendmail extends \Zend_Mail_Transport_Sendmail
{

}