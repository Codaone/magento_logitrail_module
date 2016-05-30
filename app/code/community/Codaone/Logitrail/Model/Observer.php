<?php

class Codaone_Logitrail_Model_Observer  extends Varien_Event_Observer {

    /*
     * Confirm order to Logitrail
     * 
     *
     */
     public function confirmOrder($observer) {
        require_once(Mage::getBaseDir('lib') . '/logitrail/lib/Logitrail/Lib/ApiClient.php');
        $api = new \Logitrail\Lib\ApiClient();
        $api->useTest(Mage::getModel('logitrail/carrier_logitrail')->isTestMode());
        $order = $observer->getEvent()->getOrder();
        $logitrailId = $order->getLogitrailOrderId();
        $api->confirmOrder($logitrailId);  
        $order->addStatusHistoryComment(Mage::helper('logitrail')->__('Logitrail Order Id: ' . $logitrailId));
        Mage::getSingleton('core/session')->setLogitrailShippingCost(0)
        if(Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
            Mage::log("Confirmed order $logitrailId", null, 'logitrail.log');
        }
     }
}       
