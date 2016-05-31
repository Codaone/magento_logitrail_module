<?php
 

class Codaone_Logitrail_Model_Logitrail extends Mage_Core_Model_Abstract {

    protected $_api = false;

    public function __construct() {
        require_once(Mage::getBaseDir('lib') . '/logitrail/lib/Logitrail/Lib/ApiClient.php');
        $api = new \Logitrail\Lib\ApiClient();
        $api->setMerchantId($this->_getConfig('merchantid'));
        $api->setSecretKey($this->_getConfig('secretkey'));
        $api->useTest(Mage::getModel('logitrail/carrier_logitrail')->isTestMode());
        $this->_api = $api;
    }        
    
    public function getApi() {
        return $this->_api;
    }   

    /*
    * Confirm order delivery to Logitrail
    *
    */
    public function confirmOrder($order) {
        Mage::getSingleton('core/session')->setLogitrailShippingCost(0);
        $api = $this->getApi();
        $api->setResponseAsRaw(true);
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

    /* 
    *
    * Add product to Logitrail
    * $param array of product id's
    * @return mixed: true on success, string error message on failure.
    *
    */
    public function addProducts($productIds) {
        $api = $this->getApi();
        $api->setResponseAsRaw(true);
        $store = Mage::app()->getStore('default');
        $taxCalculation = Mage::getModel('tax/calculation');
        $request = $taxCalculation->getRateRequest(null, null, null, $store);
        
        $api->clearAll();
        foreach($productIds as $productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $taxClassId = $product->getTaxClassId();
            $taxPercent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
            $api->addProduct($product->getId(),
                             $product->getName(),
                             $product->getStockItem()->getQty(),
                             $product->getWeight() * 1000, // in grams
                             $product->getPrice(),
                             $percent,
                             $product->getBarcode(), 
                             $product->getWidth(), // width
                             $product->getHeight(), // height
                             $product->getLength() // length
                            );
       }
        $results = $api->createProducts();
        $success = true;
        $failed = array();
        $errorMessage = '';
        foreach ($results as $result) {
            $status = json_decode($result, true);
            if ($status === false) {
                // not correct json
                $success = true;
                $errorMessage = Mage::helper('logitrail')->__('Error creating/updating products');
                Mage::log("Error: could not create/update product Logitrail. Response: " . print_r($results, true), Zend_Log::ERR);
                if (Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
                     Mage::log("Error: could not create/update product Logitrail. Response: " . print_r($results, true), null, 'logitrail.log');
          }
                return $errorMessage;  // can not recover
            }
            if ($status['success'] != 1) {
                $success = false;
                $failed[] = $status['id'];
            }
      }
        if(!$success) {
          $errorMessage = Mage::helper('logitrail')->__('Failed creating/updating product IDs: ') . join(', ', $failed); 
          Mage::log("Error: could not create/update product Logitrail. Response: " . join(",", $results), Zend_Log::ERR);
          if (Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
                Mage::log("Error: could not create product to Logitrail. Logitrail Order Id:  $logitrailId Response:  " . print_r($results, true), null, 'logitrail.log');
 
         }
         return $errorMessage;
      }
        if (Mage::getModel('logitrail/carrier_logitrail')->isTestMode()) {
                    Mage::log("Created/updated products to Logitrail. Product IDs: " . join(',',$productIds) . " Logitrail response " .   print_r($results, true), null, 'logitrail.log');
        }
        return true;
    }

    protected function _getConfig($name) {
            return Mage::getStoreConfig('carriers/logitrail/' . $name);
        }
}

