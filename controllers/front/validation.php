<?php

class Cci_CouriercashondeliveryValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /** @var Cci_Couriercashondelivery */
    public $module;

    public function postProcess()
    {
        if (!$this->isContextValid() || !$this->module->isCarrierAllowed((int) $this->context->cart->id_carrier)) {
            $this->redirectToCheckout();
        }

        $customer = new Customer((int) $this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer) || !$this->isPaymentModuleAvailable()) {
            $this->redirectToCheckout();
        }

        $orderStateId = $this->module->getOrderStateId();
        if ($orderStateId <= 0) {
            throw new PrestaShopException('Courier cash-on-delivery order state is not configured.');
        }

        $this->module->validateOrder(
            (int) $this->context->cart->id,
            $orderStateId,
            (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->module->getPaymentLabel(),
            null,
            [],
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect($this->context->link->getPageLink(
            'order-confirmation',
            true,
            (int) $this->context->language->id,
            [
                'id_cart' => (int) $this->context->cart->id,
                'id_module' => (int) $this->module->id,
                'id_order' => (int) $this->module->currentOrder,
                'key' => $customer->secure_key,
            ]
        ));
    }

    private function isContextValid(): bool
    {
        return Validate::isLoadedObject($this->context->cart)
            && Validate::isUnsignedInt($this->context->cart->id_customer)
            && Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && Validate::isUnsignedInt($this->context->cart->id_address_invoice)
            && !$this->context->cart->isVirtualCart();
    }

    private function isPaymentModuleAvailable(): bool
    {
        foreach (Module::getPaymentModules() as $module) {
            if (($module['name'] ?? null) === $this->module->name) {
                return true;
            }
        }

        return false;
    }

    private function redirectToCheckout(): void
    {
        Tools::redirect($this->context->link->getPageLink(
            'order',
            true,
            (int) $this->context->language->id,
            ['step' => 1]
        ));
    }
}
