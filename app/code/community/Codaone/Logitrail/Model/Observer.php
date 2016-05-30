<?php

class Codaone_Logitrail_Model_Observer  extends Varien_Event_Observer {

    /*
     * Confirm order to Logitrail
     * 
     *
     */
     public function confirmOrder($observer) {
         Mage::getSingleton('core/session')->setLogitrailShippingCost(0);
        require_once(Mage::getBaseDir('lib') . '/logitrail/lib/Logitrail/Lib/ApiClient.php');
        $api = new \Logitrail\Lib\ApiClient();
        $api->setMerchantId($this->_getConfig('merchantid'));
        $api->setSecretKey($this->_getConfig('secretkey'));
        $api->useTest(Mage::getModel('logitrail/carrier_logitrail')->isTestMode());
        $order = $observer->getEvent()->getOrder();
        $logitrailId = $order->getLogitrailOrderId();
        $rawResponse = $api->confirmOrder($logitrailId);  
        $response = json_decode($rawResponse, true);
        if ($response) {

            if ($this->_getConfig('autoship')  and $order->canShip()) { 
                $qty=array();
                foreach($order->getAllItems() as $item){
 
                    $Itemqty = $item->getQtyOrdered();
                    $qty[$item->getId()] = $item->getQtyOrdered();
                }

                $shipment =  $order->prepareShipment($qty);
                $shipment->register();
                $shipment->addComment(Mage::helper('logitrail')->__("Tracking URL: " . str_replace('\\', '', $response['tracking_url'])));
                $track = Mage::getModel('sales/order_shipment_track')->addData(array('carrier_code' => 'custom',
                                                                                 'title' => 'Logitrail',
                                                                                 'number' => $response['tracking_code']));    
                $shipment->addTrack($track);
                $shipment->getOrder()->setIsInProcess(true);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($order)
                    ->save();
              
                $shipment->sendEmail(true, Mage::helper('logitrail')->__("Tracking URL: " . str_replace('\\', '', $response['tracking_url'])));
                $shipment->setEmailSent(true);
                $order->save();   
            } // if autoship  

              $order->addStatusHistoryComment(sprintf(Mage::helper('logitrail')->__("Logitrail Order Id: %s, Tracking number: %s, Tracking URL: %s"), $logitrailId,  $response['tracking_code'],  str_replace('\\','', $response['tracking_url'])));


         if (Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
            Mage::log("Confirmed order $logitrailId, response $rawResponse", null, 'logitrail.log');
          }
           
        } else {  // confirmation failed
            $order->addStatusHistoryComment(Mage::helper('logitrail')->__('Error: could not confirm order to Logitrail. Logitrail Order Id: ' . $logitrailId));
            Mage::log("Error: could not confirm order to Logitrail. Logitrail Order Id:  $logitrailId Response: $rawResponse", Zend_Log::ERR);
            if (Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
                Mage::log("Error: could not confirm order to Logitrail. Logitrail Order Id:  $logitrailId Response: $rawResponse", null, 'logitrail.log');
            }
        }        
       
     }

      protected function _getConfig($name) {
            return Mage::getStoreConfig('carriers/logitrail/' . $name);
        }

}       
