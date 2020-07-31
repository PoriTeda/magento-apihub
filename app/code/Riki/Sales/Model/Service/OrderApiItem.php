<?php

namespace Riki\Sales\Model\Service;


use Riki\Sales\Api\OrderApiItemInterface;

class OrderApiItem implements OrderApiItemInterface
{

    protected $status;


    /**
     * @return string |null
     */
    public function getStatus(){
        return $this->status;
    }
    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status){
        return $this->status = $status;
    }


}


