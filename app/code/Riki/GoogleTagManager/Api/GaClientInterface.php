<?php

namespace Riki\GoogleTagManager\Api;


interface GaClientInterface
{
    /**
     * Process data of ga client id
     *
     * @param string $gaClientId
     * @return mixed
     */
    public function processGaClient($gaClientId);

}