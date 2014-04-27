<?php

class Komplizierte_Shoppilot_Model_Observer extends Varien_Event_Observer
{
    public function __construct()
    {
    }

    public function send ($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $url = Mage::getStoreConfig('komplizierte_shoppilot/main/url');
        $data["auth_token"] = Mage::getStoreConfig('komplizierte_shoppilot/main/token');
        $data["number"] = $order->getIncrementId();
        $data["email"] = $order->getCustomerEmail();
        $data["full_name"] = $order->getCustomerFirstname().' '.$order->getCustomerLastname();
        $data["created_at"] = strtotime(date('Y-m-d H:i:s'));
        $data["details"]["first_name"] = $order->getCustomerFirstname();
        $data["details"]["last_name"] = $order->getCustomerLastname();
        $data["details"]["financial_status"] = "new";
        $data["details"]["fulfillment_status"] = "new";
        $data["details"]["delivery_service"] =  $order->getShippingDescription();
        $data["details"]["delivery_city"] = $order->getBillingAddress()->getCity();
        $data["details"]["amount"] = $order->getGrandTotal();

        $items=$order->getAllItems();
        foreach($items as $item){
            $data["order_lines"][]=
                array("product_id"=>$item->getProductId(),
                    "title"=>$item->getName(),
                    "brand"=>$item->getProduct()->load()->getAttributeText('manufacturer'),
                    "price"=>$item->getPrice()
                );
        }


        $this->send_data($data, $url, "POST");
    }

    public function update ($observer){
        $url = Mage::getStoreConfig('komplizierte_shoppilot/main/url').'/'.$observer->getOrder()->getIncrementId();
        $data["auth_token"] = Mage::getStoreConfig('komplizierte_shoppilot/main/token');

        $data["updated_at"] = strtotime(date('Y-m-d H:i:s'));
        $invoice_status ='new';
        $shipment_status = 'new';
        if ($observer->getOrder()->getInvoiceCollection()->count()>0)   $invoice_status ='paid';
        else $invoice_status = $observer->getOrder()->getStatus();
        if ($observer->getOrder()->getShipmentsCollection()->count()>0) $shipment_status='completed';
        else $shipment_status =  $observer->getOrder()->getStatus();
        $data["details"] = array("financial_status" => $invoice_status,
            "fulfillment_status" => $shipment_status
        );
        $this->send_data($data, $url, "PUT");
    }

    /**
     * @param $data
     * @param $url
     */
    private function send_data($data, $url, $method)
    {
        $data_string = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
            )
        );

        $result = curl_exec($ch);
        echo $result;
        curl_close($ch);
    }


}
