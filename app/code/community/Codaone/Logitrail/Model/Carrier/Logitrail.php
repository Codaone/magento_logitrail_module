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
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $items = $quote->getAllVisibleItems();
            $api = Mage::getModel('logitrail/logitrail')->getApi();
            $api->setOrderId($quote->getId());
            foreach($items as $item) {
                $api->addProduct($item->getProductId(),
                                $item->getName(), 
                                $item->getQty(), 
                                $item->getWeight(),
                                $item->getPrice(),
                                $item->getTaxPercent());
            }
            $address = $quote->getShippingAddress();
            $api->setCustomerInfo($address->getFirstname(),
                                  $address->getLastname(),
                                  join(' ', $address->getStreet()),
                                  $address->getPostcode(),
                                  $address->getCity());
            $form = $api->getForm();
            if ($this->isTestMode()) { 
                Mage::log("Order form for Logitrail: $form", null, 'logitrail.log');
            }
            return $form;
        }                   
        /**
        * Update shipping details with logittail order id and shipping fee
        *
        * @return string 
        */
        public function shippingDetails($logitrailId, $price) {
             if ($this->isTestMode()) { 
                    Mage::log("Shipping details: Logitrail Order Id: $logitrailId, Shipping fee: $price", null, 'logitrail.log');
            }
            Mage::getSingleton('core/session')->setLogitrailShippingCost($price);
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

           $quote->setData('logitrail_order_id', $logitrailId)->save();
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
		    $method->setPrice(Mage::getSingleton('core/session')->getLogitrailShippingCost());
		    $method->setCost(Mage::getSingleton('core/session')->getLogitrailShippingCost()); 
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
            return $this->getConfigData('testmode') == 1;
        }
    }  
