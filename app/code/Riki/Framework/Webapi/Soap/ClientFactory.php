<?php
/**
 * Framework
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Framework
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Framework\Webapi\Soap;

/**
 * Class ClientFactory
 *
 * @category  RIKI
 * @package   Riki\Framework\Webapi
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ClientFactory
{
    /**
     * @param $wsdl
     * @param array $options
     * @return \Zend\Soap\Client
     * @throws \Exception
     */
    public function create($wsdl, array $options = [])
    {
        $soapClient = new \Zend\Soap\Client($wsdl, $options);

        try {
            $soapClientClient = new \SoapClient($soapClient->getWSDL(), array_merge($soapClient->getOptions(), [
                'exceptions' => true
            ]));
        } catch (\Exception $e) {
            $lastError = error_get_last();
            if ($lastError['type'] === E_ERROR) {
                set_error_handler('var_dump', 0); // Never called because of empty mask.
                @trigger_error("");
                restore_error_handler();
            }

            throw $e;
        }

        return $soapClient->setSoapClient($soapClientClient);
    }
}
