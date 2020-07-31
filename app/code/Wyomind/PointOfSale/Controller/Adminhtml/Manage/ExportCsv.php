<?php

namespace Wyomind\PointOfSale\Controller\Adminhtml\Manage;

class ExportCsv extends \Wyomind\PointOfSale\Controller\Adminhtml\PointOfSale
{

    public function execute()
    {


        $fileName = "pointofsale.csv";

        $header = "";
        $header.="customer_group" . "\t";
        $header.="store_id" . "\t";
        $header.="order" . "\t";
        $header.="store_code" . "\t";
        $header.="name" . "\t";
        $header.="address_line_1" . "\t";
        $header.="address_line_2" . "\t";
        $header.="city" . "\t";
        $header.="state" . "\t";
        $header.="postal_code" . "\t";
        $header.="country_code" . "\t";
        $header.="main_phone" . "\t";
        $header.="email" . "\t";
        $header.="hours" . "\t";
        $header.="description" . "\t";
        $header.="longitude" . "\t";
        $header.="latitude" . "\t";
        $header.="status" . "\t";
        $header.="image" . "\t";

        $content = "";

        $places = clone $this->_posCollection;
        foreach ($places as $place) {
            $content.= $place->getData("customer_group") . "\t";
            $content.= $place->getData("store_id") . "\t";
            $content.= $place->getData("order") . "\t";
            $content.= $place->getData("store_code") . "\t";
            $content.= $place->getData("name") . "\t";
            $content.= $place->getData("address_line_1") . "\t";
            $content.= $place->getData("address_line_2") . "\t";
            $content.= $place->getData("city") . "\t";
            $content.= $place->getData("state") . "\t";
            $content.= $place->getData("postal_code") . "\t";
            $content.= $place->getData("country_code") . "\t";
            $content.= $place->getData("main_phone") . "\t";
            $content.= $place->getData("email") . "\t";
            $content.= $place->getData("hours") . "\t";
            $content.= $place->getData("description") . "\t";
            $content.= $place->getData("longitude") . "\t";
            $content.= $place->getData("latitude") . "\t";
            $content.= $place->getData("status") . "\t";
            $content.= $place->getData("image") . "\t";
            $content.= "\r\n";
        }

        return $this->_coreHelper->sendUploadResponse($fileName, $header . "\r\n" . $content);
    }
}
