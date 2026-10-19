<?php

/** Back-office presentation; installation, hooks and business rules stay in the module. */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class CciCourierCashOnDeliveryAdminPresentation
{
    public function __construct(
        private readonly Module $module,
        private readonly Context $context,
    ) {
    }

    public function renderHeroSection(): string
    {
        $heroImage = $this->getBrandAssetUrl('views/img/cci-hello.png');
        $heroStyle = $heroImage !== ''
            ? ' style="--cci-hero-image: url(\'' . $this->escapeHtml($heroImage) . '\')"'
            : '';

        return '<div class="cci-card cci-hero"' . $heroStyle . '><div class="cci-card-inner">'
            . '<div class="cci-hero-content"><div class="cci-title-row">'
            . '<span class="cci-title">' . $this->escapeHtml($this->module->displayName) . '</span>'
            . '<span class="cci-version">v' . $this->escapeHtml($this->module->version) . '</span>'
            . '</div><p class="cci-subtitle">' . $this->escapeHtml($this->module->getTranslator()->trans('A separate payment method for courier cash-on-delivery orders.', [], 'Modules.CciCouriercashondelivery.Admin')) . '</p>'
            . '</div>'
            . '</div></div>';
    }

    public function renderBrandPanel(): string
    {
        $coolCatIdeasLogo = $this->getBrandAssetUrl('views/img/cool-cat-ideas-logo.svg');
        $teaStudioLogo = $this->getBrandAssetUrl('views/img/teastudio-logo.png');
        $html = '<aside class="cci-brand-panel"><div class="cci-brand-card">';
        $html .= '<div class="cci-brand-title">' . $this->escapeHtml($this->brandTrans('About us')) . '</div>';
        $html .= '<div class="cci-brand-logos">';
        if ($coolCatIdeasLogo !== '') {
            $html .= '<img src="' . $this->escapeHtml($coolCatIdeasLogo) . '" alt="Cool Cat Ideas">';
        }
        if ($teaStudioLogo !== '') {
            $html .= '<img src="' . $this->escapeHtml($teaStudioLogo) . '" alt="teastudio">';
        }
        $html .= '</div>';
        $html .= '<p class="cci-brand-text">'
            . $this->escapeHtml($this->brandTrans('This module is only a fraction of what we do. We deliver custom IT solutions that grow with your business. We ensure complete code optimization and security.'))
            . '</p>';
        $html .= '<div class="cci-brand-heading">' . $this->escapeHtml($this->brandTrans('We implement and develop:')) . '</div>';
        $html .= '<ul class="cci-check-list">';
        $html .= '<li><span class="cci-feature-text">' . $this->renderBrandFeature('Fast e-commerce on %highlight% (from implementations to custom modules)', 'PrestaShop') . '</span></li>';
        $html .= '<li><span class="cci-feature-text">' . $this->renderBrandFeature('Flexible and modern %highlight% websites', 'WordPress') . '</span></li>';
        $html .= '<li><span class="cci-feature-text">' . $this->renderBrandFeature('Scalable web applications in %highlight%', 'Next.js') . '</span></li>';
        $html .= '<li><span class="cci-feature-text">' . $this->renderBrandFeature('Process automation and %highlight%', 'API integrations') . '</span></li>';
        $html .= '</ul>';
        $html .= '<a class="cci-btn-primary" href="https://coolcatideas.com" target="_blank" rel="noopener noreferrer">'
            . $this->escapeHtml($this->brandTrans('See our offer')) . '</a>';
        $html .= '</div></aside>';

        return $html;
    }

    private function renderBrandFeature(string $source, string $highlight): string
    {
        $translated = $this->escapeHtml($this->brandTrans($source));
        $translatedHighlight = $this->escapeHtml($this->brandTrans($highlight));

        return str_replace('%highlight%', '<strong>' . $translatedHighlight . '</strong>', $translated);
    }

    private function getBrandAssetUrl(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $filePath = $this->module->getLocalPath() . $relativePath;

        return is_file($filePath)
            ? $this->module->getPathUri() . $relativePath
            : '';
    }

    private function brandTrans(string $source): string
    {
        try {
            return (string) $this->context->getTranslator()->trans(
                $source,
                [],
                'Modules.CciCouriercashondelivery.Admin'
            );
        } catch (Throwable $exception) {
            return $source;
        }
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
