<?php
namespace Bluecom\Paygent\Api;

interface PaygentManagementInterface
{
    /**
     * Send email reauthorize failure
     *
     * @param array $params
     *
     * @return bool
     */
    public function sendEmailReauthorizeFailure($params);

    /**
     * Get redirect authorize link
     *
     * @param $params
     *
     * @return array
     */
    public function getRedirectAuthorizeLink($params);


    /**
     * Get error
     *
     * @param $params
     *
     * @return string
     */
    public function getErrorMessage($params);
}