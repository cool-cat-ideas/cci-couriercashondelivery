<?php

/**
 * CCI Courier Cash on Delivery for PrestaShop.
 *
 * @author    Cool Cat Ideas <info@coolcatideas.com>
 * @copyright 2026 Cool Cat Ideas
 * @license   https://opensource.org/license/afl-3-0-php Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once __DIR__ . '/classes/Admin/CciCourierCashOnDeliveryAdminPresentation.php';

class Cci_Couriercashondelivery extends PaymentModule
{
    public const PAYMENT_LABEL = 'Płatność za pobraniem - zapłać u kuriera';
    public const PAYMENT_DESCRIPTION = 'Zapłać gotówką przy odbiorze';

    private const CONFIG_CARRIER_REFERENCES = 'CCI_COURIER_COD_CARRIER_REFERENCES';
    private const CONFIG_ORDER_STATE = 'CCI_COURIER_COD_ORDER_STATE';
    private const DEFAULT_CARRIER_REFERENCES = [75, 62];
    private const ADMIN_PARENT_TAB_CLASS = 'AdminCciParent';
    private const ADMIN_TAB_CLASS = 'AdminCciCourierCashOnDelivery';

    private ?CciCourierCashOnDeliveryAdminPresentation $adminPresentation = null;

    public function __construct()
    {
        $this->name = 'cci_couriercashondelivery';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'Cool Cat Ideas';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->controllers = ['validation'];
        $this->currencies = false;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.2.999',
        ];

        parent::__construct();

        $this->displayName = $this->trans(
            'CCI Courier Cash on Delivery',
            [],
            'Modules.CciCouriercashondelivery.Admin'
        );
        $this->description = $this->trans(
            'Offer courier cash on delivery for selected existing carriers.',
            [],
            'Modules.CciCouriercashondelivery.Admin'
        );
        $this->confirmUninstall = $this->trans(
            'Are you sure you want to uninstall the courier cash-on-delivery module?',
            [],
            'Modules.CciCouriercashondelivery.Admin'
        );
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $references = $this->resolveDefaultCarrierReferences();

        return $this->registerHook('paymentOptions')
            && $this->registerHook('displayOrderConfirmation')
            && Configuration::updateValue(self::CONFIG_CARRIER_REFERENCES, json_encode($references))
            && $this->installOrderState()
            && $this->synchronizeCarrierRestrictions($references)
            && $this->synchronizeStandardCashOnDelivery([], $references)
            && $this->ensureAdminTab();
    }

    public function uninstall()
    {
        $references = $this->getConfiguredCarrierReferences();
        $standardModuleRestored = $this->synchronizeStandardCashOnDelivery($references, []);
        $configurationDeleted = Configuration::deleteByName(self::CONFIG_CARRIER_REFERENCES);

        // The order state is intentionally preserved for historical orders.
        return $standardModuleRestored
            && $configurationDeleted
            && $this->uninstallAdminTab()
            && parent::uninstall();
    }

    public function hookPaymentOptions(array $params): array
    {
        if (empty($params['cart']) || !$params['cart'] instanceof Cart) {
            return [];
        }

        /** @var Cart $cart */
        $cart = $params['cart'];
        if ($cart->isVirtualCart() || !$this->isCarrierAllowed((int) $cart->id_carrier)) {
            return [];
        }

        $this->context->smarty->assign('cci_courier_cod_description', $this->getPaymentDescription());

        $option = new PaymentOption();
        $option->setModuleName($this->name);
        $option->setCallToActionText($this->getPaymentLabel());
        $option->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true));
        $option->setAdditionalInformation(
            $this->fetch('module:cci_couriercashondelivery/views/templates/hook/payment_options.tpl')
        );

        return [$option];
    }

    public function hookDisplayOrderConfirmation(array $params): string
    {
        /** @var Order|null $order */
        $order = $params['objOrder'] ?? $params['order'] ?? null;
        if (!$order instanceof Order || !Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'cci_courier_cod_label' => $this->getPaymentLabel(),
            'cci_courier_cod_contact_url' => $this->context->link->getPageLink('contact', true),
        ]);

        return $this->fetch('module:cci_couriercashondelivery/views/templates/hook/order_confirmation.tpl');
    }

    public function isCarrierAllowed(int $idCarrier): bool
    {
        if ($idCarrier <= 0) {
            return false;
        }

        $carrier = new Carrier($idCarrier);

        return Validate::isLoadedObject($carrier)
            && in_array((int) $carrier->id_reference, $this->getConfiguredCarrierReferences(), true);
    }

    public function getPaymentLabel(): string
    {
        return $this->trans('Courier cash on delivery', [], 'Modules.CciCouriercashondelivery.Shop');
    }

    public function getPaymentDescription(): string
    {
        return $this->trans('Pay the courier when your order arrives.', [], 'Modules.CciCouriercashondelivery.Shop');
    }

    public function getOrderStateId(): int
    {
        return (int) Configuration::get(self::CONFIG_ORDER_STATE);
    }

    private function canSaveAdminSettings(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || !Validate::isLoadedObject($this->context->employee)
            || !$this->context->employee->hasAuthOnShop((int) $this->context->shop->id)) {
            return false;
        }
        $controller = $this->context->controller;
        if ($controller instanceof AdminController) {
            return $controller->access('edit') && $controller->checkToken();
        }
        // The module manager in PrestaShop 9 uses a Symfony legacy context.
        if (!$controller instanceof \PrestaShop\PrestaShop\Core\Context\LegacyControllerContext
            || !$this->getPermission('configure')) {
            return false;
        }
        $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        return $container !== null
            && $container->get(\PrestaShopBundle\Security\Admin\UserTokenManager::class)->isTokenValid();
    }

    public function getContent(): string
    {
        $this->ensureAdminTab();
        $this->registerAdminAssets();
        $output = '';

        if (Tools::isSubmit('submitCciCourierCashOnDelivery')) {
            if (!$this->canSaveAdminSettings()) {
                http_response_code(403);
                return $this->displayError($this->l('You do not have permission to change these settings.'));
            }

            $output .= $this->saveConfiguration();
        }

        return '<div class="cci-courier-cod-shell cci-admin-shell bootstrap">'
            . '<div class="cci-admin-main">'
            . $output
            . $this->getAdminPresentation()->renderHeroSection()
            . $this->renderSettingsCard()
            . '</div>'
            . $this->getAdminPresentation()->renderBrandPanel()
            . '</div>';
    }

    public function ensureAdminTab(): bool
    {
        $parentId = $this->ensureCciParentTab();
        if ($parentId <= 0) {
            return false;
        }

        $tabId = (int) Tab::getIdFromClassName(self::ADMIN_TAB_CLASS);
        $tab = $tabId > 0 ? new Tab($tabId) : new Tab();
        $tab->active = 1;
        $tab->class_name = self::ADMIN_TAB_CLASS;
        $tab->id_parent = $parentId;
        $tab->icon = 'local_shipping';
        $tab->module = $this->name;

        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = 'CCI Courier Cash on Delivery';
        }

        $saved = $tabId > 0 ? (bool) $tab->update() : (bool) $tab->add();
        if (!$saved) {
            return false;
        }

        return $this->normalizeCciMenuPositions($parentId);
    }

    private function ensureCciParentTab(): int
    {
        $improveTabId = (int) Tab::getIdFromClassName('IMPROVE');
        if ($improveTabId <= 0) {
            return 0;
        }

        $tabId = (int) Tab::getIdFromClassName(self::ADMIN_PARENT_TAB_CLASS);
        $tab = $tabId > 0 ? new Tab($tabId) : new Tab();
        $tab->active = 1;
        $tab->class_name = self::ADMIN_PARENT_TAB_CLASS;
        $tab->id_parent = $improveTabId;
        $tab->icon = 'pets';
        $tab->module = '';
        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = 'Cool Cat Ideas';
        }

        $saved = $tabId > 0 ? (bool) $tab->update() : (bool) $tab->add();
        if (!$saved) {
            return 0;
        }

        $modulesTabId = (int) Tab::getIdFromClassName('AdminParentModulesSf');
        if ($modulesTabId > 0) {
            $modulesTab = new Tab($modulesTabId);
            $tab = new Tab((int) $tab->id);
            if ((int) $modulesTab->id_parent === $improveTabId) {
                $desiredPosition = (int) $modulesTab->position + 1;
                if ((int) $tab->position !== $desiredPosition) {
                    $tab->updatePosition((int) $tab->position < $desiredPosition, $desiredPosition);
                }
            }
        }

        $tab->cleanPositions($improveTabId);

        return (int) $tab->id;
    }

    private function normalizeCciMenuPositions(int $parentId): bool
    {
        $tabs = Db::getInstance()->executeS(
            'SELECT `id_tab`, `class_name`, `position` FROM `' . _DB_PREFIX_ . 'tab`'
            . ' WHERE `id_parent` = ' . $parentId
        );
        if (!is_array($tabs)) {
            return false;
        }

        $priorities = [
            'AdminCciBlog' => 10,
            'AdminCciWithdrawal' => 20,
            'AdminCciConsentHistory' => 25,
            'AdminCciNiceMenu' => 30,
            'AdminCciCategoryGrid' => 40,
            'AdminCciCourierCashOnDelivery' => 50,
        ];
        usort($tabs, static function (array $left, array $right) use ($priorities): int {
            $leftPriority = $priorities[$left['class_name']] ?? 1000 + (int) $left['position'];
            $rightPriority = $priorities[$right['class_name']] ?? 1000 + (int) $right['position'];

            return $leftPriority <=> $rightPriority ?: (int) $left['id_tab'] <=> (int) $right['id_tab'];
        });

        foreach ($tabs as $index => $tabData) {
            if (!Db::getInstance()->update('tab', ['position' => $index + 1], 'id_tab = ' . (int) $tabData['id_tab'])) {
                return false;
            }
        }

        return true;
    }

    private function uninstallAdminTab(): bool
    {
        $tabId = (int) Tab::getIdFromClassName(self::ADMIN_TAB_CLASS);
        if ($tabId > 0 && !(bool) (new Tab($tabId))->delete()) {
            return false;
        }

        $parentId = (int) Tab::getIdFromClassName(self::ADMIN_PARENT_TAB_CLASS);
        if ($parentId <= 0) {
            return true;
        }

        $remainingChildren = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'tab` WHERE `id_parent` = ' . $parentId
        );

        return $remainingChildren > 0 || (bool) (new Tab($parentId))->delete();
    }

    /** @return int[] */
    private function getConfiguredCarrierReferences(): array
    {
        $decoded = json_decode((string) Configuration::get(self::CONFIG_CARRIER_REFERENCES), true);
        if (!is_array($decoded)) {
            return [];
        }

        $references = array_values(array_unique(array_filter(array_map('intval', $decoded))));
        sort($references);

        return $references;
    }

    /** @return int[] */
    private function resolveDefaultCarrierReferences(): array
    {
        $available = $this->getCarrierChoices();
        $references = [];

        foreach ($available as $carrier) {
            if (in_array((int) $carrier['id_reference'], self::DEFAULT_CARRIER_REFERENCES, true)) {
                $references[] = (int) $carrier['id_reference'];
            }
        }

        return $references !== [] ? array_values(array_unique($references)) : self::DEFAULT_CARRIER_REFERENCES;
    }

    /** @return array<int, array{id_reference: int, name: string}> */
    private function getCarrierChoices(): array
    {
        $rows = Carrier::getCarriers(
            (int) $this->context->language->id,
            false,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );
        $choices = [];

        foreach ($rows as $row) {
            $reference = (int) ($row['id_reference'] ?? 0);
            if ($reference <= 0 || isset($choices[$reference])) {
                continue;
            }

            $choices[$reference] = [
                'id_reference' => $reference,
                'name' => trim((string) ($row['name'] ?? 'Przewoźnik #' . $reference)),
            ];
        }

        uasort($choices, static fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return array_values($choices);
    }

    private function saveConfiguration(): string
    {
        $previousReferences = $this->getConfiguredCarrierReferences();
        $references = [];

        foreach ($this->getCarrierChoices() as $carrier) {
            $reference = (int) $carrier['id_reference'];
            if ((bool) Tools::getValue('carrier_references_' . $reference, false)) {
                $references[] = $reference;
            }
        }

        if ($references === []) {
            return $this->displayError('Wybierz co najmniej jednego przewoźnika dla płatności za pobraniem.');
        }

        if (!Configuration::updateValue(self::CONFIG_CARRIER_REFERENCES, json_encode($references))) {
            return $this->displayError('Nie udało się zapisać konfiguracji przewoźników.');
        }

        if (
            !$this->synchronizeCarrierRestrictions($references)
            || !$this->synchronizeStandardCashOnDelivery($previousReferences, $references)
        ) {
            Configuration::updateValue(self::CONFIG_CARRIER_REFERENCES, json_encode($previousReferences));
            $this->synchronizeCarrierRestrictions($previousReferences);
            $this->synchronizeStandardCashOnDelivery($references, $previousReferences);

            return $this->displayError('Nie udało się zsynchronizować ograniczeń przewoźników.');
        }

        return $this->displayConfirmation('Ustawienia zostały zapisane.');
    }

    /** @param int[] $references */
    private function synchronizeCarrierRestrictions(array $references): bool
    {
        $db = Db::getInstance();
        if (!$db->delete('module_carrier', 'id_module = ' . (int) $this->id)) {
            return false;
        }

        $rows = [];
        foreach (Shop::getShops(true, null, true) as $idShop) {
            foreach ($references as $reference) {
                $rows[] = [
                    'id_module' => (int) $this->id,
                    'id_shop' => (int) $idShop,
                    'id_reference' => (int) $reference,
                ];
            }
        }

        return $rows === [] || $db->insert('module_carrier', $rows, false, true, Db::INSERT_IGNORE);
    }

    /**
     * Keep ps_cashondelivery for pickup and this module for courier COD.
     *
     * @param int[] $referencesToRestore
     * @param int[] $referencesToDisable
     */
    private function synchronizeStandardCashOnDelivery(array $referencesToRestore, array $referencesToDisable): bool
    {
        $standardModuleId = (int) Module::getModuleIdByName('ps_cashondelivery');
        if ($standardModuleId <= 0) {
            return true;
        }

        $db = Db::getInstance();
        $shops = Shop::getShops(true, null, true);
        $rows = [];

        foreach ($shops as $idShop) {
            foreach ($referencesToRestore as $reference) {
                $rows[] = [
                    'id_module' => $standardModuleId,
                    'id_shop' => (int) $idShop,
                    'id_reference' => (int) $reference,
                ];
            }
        }

        if ($rows !== [] && !$db->insert('module_carrier', $rows, false, true, Db::INSERT_IGNORE)) {
            return false;
        }

        if ($referencesToDisable === []) {
            return true;
        }

        $disabled = implode(', ', array_map('intval', $referencesToDisable));

        return $db->delete(
            'module_carrier',
            'id_module = ' . $standardModuleId . ' AND id_reference IN (' . $disabled . ')'
        );
    }

    private function installOrderState(): bool
    {
        $existing = new OrderState($this->getOrderStateId());
        if (Validate::isLoadedObject($existing)) {
            return true;
        }

        $state = new OrderState();
        $state->module_name = $this->name;
        $state->color = '#4169E1';
        $state->logable = false;
        $state->paid = false;
        $state->invoice = false;
        $state->send_email = false;
        $state->hidden = false;
        $state->delivery = false;
        $state->shipped = false;
        $state->unremovable = true;
        $state->name = [];

        foreach (Language::getLanguages(false) as $language) {
            $locale = strtolower((string) ($language['locale'] ?? $language['iso_code'] ?? ''));
            $state->name[(int) $language['id_lang']] = str_starts_with($locale, 'pl')
                ? 'Oczekiwanie na płatność za pobraniem u kuriera'
                : 'Awaiting courier cash on delivery';
        }

        return $state->add()
            && Configuration::updateValue(self::CONFIG_ORDER_STATE, (int) $state->id);
    }

    private function renderSettingsCard(): string
    {
        return '<div class="cci-card cci-card--settings"><div class="cci-card-inner">'
            . '<div class="cci-section-title"><h3>' . $this->escapeHtml($this->trans('Carriers offering cash on delivery', [], 'Modules.CciCouriercashondelivery.Admin')) . '</h3></div>'
            . '<p class="cci-subtitle">' . $this->escapeHtml($this->trans('For selected carriers, courier cash on delivery replaces the standard cash-on-delivery option.', [], 'Modules.CciCouriercashondelivery.Admin')) . '</p>'
            . '<div class="cci-form-wrapper">' . $this->renderSettingsForm() . '</div>'
            . '</div></div>';
    }

    private function renderSettingsForm(): string
    {
        $carrierInputs = [];
        foreach ($this->getCarrierChoices() as $carrier) {
            $reference = (int) $carrier['id_reference'];
            $fieldName = 'carrier_references_' . $reference;
            $carrierInputs[] = [
                'type' => 'switch',
                'label' => (string) $carrier['name'],
                'name' => $fieldName,
                'is_bool' => true,
                'desc' => $this->trans('Carrier reference: %reference%', ['%reference%' => $reference], 'Modules.CciCouriercashondelivery.Admin'),
                'values' => [
                    [
                        'id' => $fieldName . '_on',
                        'value' => 1,
                        'label' => $this->transCore('Enabled', 'Admin.Global'),
                    ],
                    [
                        'id' => $fieldName . '_off',
                        'value' => 0,
                        'label' => $this->transCore('Disabled', 'Admin.Global'),
                    ],
                ],
            ];
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Courier payment settings', [], 'Modules.CciCouriercashondelivery.Admin'),
                    'icon' => 'icon-truck',
                ],
                'input' => $carrierInputs,
                'submit' => [
                    'title' => $this->transCore('Save', 'Admin.Actions'),
                    'name' => 'submitCciCourierCashOnDelivery',
                    'icon' => 'icon-save',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCciCourierCashOnDelivery';
        $helper->currentIndex = $this->context->link->getAdminLink(self::ADMIN_TAB_CLASS, false);
        $helper->token = Tools::getAdminTokenLite(self::ADMIN_TAB_CLASS);
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => method_exists($this->context->controller, 'getLanguages')
                ? $this->context->controller->getLanguages()
                : Language::getLanguages(false),
            'id_language' => (int) $this->context->language->id,
        ];

        return $this->flattenHelperFormPanel($helper->generateForm([$fieldsForm]));
    }

    private function flattenHelperFormPanel(string $formHtml): string
    {
        $flattened = preg_replace(
            '~<div class="panel" id="fieldset_[^"]*">\s*<div class="panel-heading">.*?</div>~s',
            '<div class="panel cci-helper-form-content">',
            $formHtml,
            1
        );

        return is_string($flattened) ? $flattened : $formHtml;
    }

    /** @return array<string, int> */
    private function getConfigFormValues(): array
    {
        $selected = $this->getConfiguredCarrierReferences();
        $values = [];

        foreach ($this->getCarrierChoices() as $carrier) {
            $reference = (int) $carrier['id_reference'];
            $values['carrier_references_' . $reference] = (int) in_array($reference, $selected, true);
        }

        return $values;
    }

    private function registerAdminAssets(): void
    {
        $controller = $this->context->controller;
        $cssPath = __DIR__ . '/views/css/admin.css';
        $version = (string) (@filemtime($cssPath) ?: $this->version);

        if (method_exists($controller, 'registerStylesheet')) {
            $controller->registerStylesheet(
                'cci-courier-cod-admin',
                'modules/' . $this->name . '/views/css/admin.css?v=' . rawurlencode($version),
                ['media' => 'all', 'priority' => 230]
            );
        } elseif (method_exists($controller, 'addCSS')) {
            $controller->addCSS($this->getPathUri() . 'views/css/admin.css?v=' . rawurlencode($version));
        }
    }

    private function transCore(string $source, string $domain = 'Admin.Actions'): string
    {
        try {
            return (string) $this->context->getTranslator()->trans($source, [], $domain);
        } catch (Throwable $exception) {
            return $source;
        }
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function getAdminPresentation(): CciCourierCashOnDeliveryAdminPresentation
    {
        return $this->adminPresentation ??= new CciCourierCashOnDeliveryAdminPresentation($this, $this->context);
    }
}
