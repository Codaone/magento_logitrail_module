<?php  
    class Codaone_Logitrail_Model_Carrier_Logitrail     
		extends Mage_Shipping_Model_Carrier_Abstract
		implements Mage_Shipping_Model_Carrier_Interface
	{  
        protected $_code = 'logitrail';  

        /**
        * Get form block name
        *
        * @return string
        */
        public function getFormBlock() {
            return 'logitrail/logitrail';
        }              
        /**
        * Get form for the checkout block
        *
        * @return string 
        */
        public function getForm() {
            require_once(Mage::getBaseDir('lib') . '/logitrail/lib/Logitrail/Lib/ApiClient.php');
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $items = $quote->getAllVisibleItems();
            $api = new \Logitrail\Lib\ApiClient();
            $api->setMerchantId($this->getConfig('merchantid'));
            $api->setSecretKey($this->getConfig('secretkey'));
            $api->setOrderId($quote->getId());
            foreach($items as $item) {
                $api->addProduct($item->getProductId(),
                                $item->getName(), 
                                $item->getQty(), 
                                $item->getWeight(),
                                $item->getPrice(),
                                $item->getTaxPercent());
            }
            $api->setCustomerInfo('etunimi', 'sukunimi','osoite','33800','tampere'); // TO DO
            $form = $api->getForm();
Mage::log($form, null, 'tero.log');
            return $form;
        }                   
        /**
        * Get form for the checkout block
        *
        * @return string 
        */
        public function shippingDetails($logitrailId, $price) {

Mage::log("shipping details $logitrailId, $price",null, 'tero.log');

        Mage::getSingleton('core/session')->setLogirailShippingCost($price);
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $address = $quote->getShippingAddress();
        $address->setShippingAmount($price);
        $address->setBaseShippingAmount($price);
        $address->save();
        // Find if our shipping has been included.
        $rates = $address->collectShippingRates()
                 ->getGroupedAllShippingRates();

        foreach ($rates as $carrier) {
            foreach ($carrier as $rate) {
                $rate->setPrice($price);
                $rate->save();            
            }
        }
        $address->setCollectShippingRates(true);    
return;
            $quote = Mage::getSingleton('checkout/session')->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
return;
            $quote->setShippingAmount($price);
            $quote->setShippingBaseAmount($price);
            $quote->save();
            $quote->collectTotals();
Mage::log(print_r($quote->getData(), true),null, 'tero.log');
        }

        /** 
        * Collect rates for this shipping method based on information in $request 
        * 
        * @param Mage_Shipping_Model_Rate_Request $request 
        * @return Mage_Shipping_Model_Rate_Result 
        */  
        public function collectRates(Mage_Shipping_Model_Rate_Request $request){ 
 
            $result = Mage::getModel('shipping/rate_result');  
            $method = Mage::getModel('shipping/rate_result_method');  
            $method->setCarrier($this->_code);  
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($this->_code);  
            $method->setMethodTitle($this->getConfigData('name'));
		    $method->setPrice(Mage::getSingleton('core/session')->getLogirailShippingCost());
		    $method->setCost(Mage::getSingleton('core/session')->getLogirailShippingCost()); 
            $result->append($method);  
            return $result;  
        }  

		/**
		 * Get allowed shipping methods
		 *
		 * @return array
		 */
		public function getAllowedMethods()
		{
			return array($this->_code=>$this->getConfigData('name'));
		}
    
            
        /**
        * Are we on test mode or not
        *
        * @return boolean
        */
        public function isTestMode() {
            return true; // to do, add config parameter
        }

        protected function getConfig($name) {
            return Mage::getStoreConfig('carriers/logitrail/' . $name);
        }

    }  
