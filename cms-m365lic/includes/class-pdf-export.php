<?php
/**
 * CMS M365 License – PDF Export
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Pdf_Export
{
    /**
     * @param array<string,mixed> $evaluation
     * @param array<int,array<string,mixed>> $requirements
     * @param array<string,string> $settings
     * @param array<string,mixed> $pricingContext
     * @param array<string,mixed> $billingContext
     * @param array<string,mixed> $pdfContext
     */
    public static function render_sanitized_export(
        array $evaluation,
        array $requirements,
        array $settings,
        array $pricingContext,
        array $billingContext,
        array $pdfContext = []
    ): string {
        $safePdfContext = self::sanitize_render_payload($pdfContext);
        if (is_array($safePdfContext)) {
            $safePdfContext['logo_path'] = '';
        }

        return self::render_html(
            self::sanitize_render_payload($evaluation),
            self::sanitize_render_payload($requirements),
            self::sanitize_render_payload($settings),
            self::sanitize_render_payload($pricingContext),
            self::sanitize_render_payload($billingContext),
            is_array($safePdfContext) ? $safePdfContext : []
        );
    }

    /**
     * @param array<string,mixed> $evaluation
     * @param array<int,array<string,mixed>> $requirements
     * @param array<string,string> $settings
     * @param array<string,mixed> $pricingContext
     * @param array<string,mixed> $billingContext
     */
    public static function render_html(array $evaluation, array $requirements, array $settings, array $pricingContext, array $billingContext, array $pdfContext = []): string
    {
        $evaluation = is_array(self::sanitize_render_payload($evaluation)) ? self::sanitize_render_payload($evaluation) : [];
        $requirements = is_array(self::sanitize_render_payload($requirements)) ? self::sanitize_render_payload($requirements) : [];
        $settings = is_array(self::sanitize_render_payload($settings)) ? self::sanitize_render_payload($settings) : [];
        $pricingContext = is_array(self::sanitize_render_payload($pricingContext)) ? self::sanitize_render_payload($pricingContext) : [];
        $billingContext = is_array(self::sanitize_render_payload($billingContext)) ? self::sanitize_render_payload($billingContext) : [];
        $pdfContext = is_array(self::sanitize_render_payload($pdfContext)) ? self::sanitize_render_payload($pdfContext) : [];
        $esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $formatMoney = static function ($value) use ($esc): string {
            if ($value === null || $value === '') {
                return '<span class="warn">offen</span>';
            }

            return $esc(number_format((float) $value, 2, ',', '.')) . ' €';
        };
        $formatMoneyText = static function ($value) use ($esc): string {
            if ($value === null || $value === '') {
                return 'offen';
            }

            return $esc(number_format((float) $value, 2, ',', '.')) . ' €';
        };
        $audienceLabel = static function (string $audience) use ($esc): string {
            return match ($audience) {
                'frontline' => $esc('Frontline / Kiosk'),
                default => $esc('Knowledge Worker'),
            };
        };
        $typeTone = static function (string $typeLabel): string {
            return $typeLabel === 'Add-on' ? 'addon' : 'base';
        };
        $featureTone = static function (string $group): string {
            return match ($group) {
                'Basis' => 'quick',
                'Zusammenarbeit' => 'advanced',
                'Security', 'Security Add-on' => 'security',
                'Identität' => 'identity',
                'Copilot' => 'copilot',
                default => 'productivity',
            };
        };
        $generatedAt = date('d.m.Y H:i');
        $variantLabel = trim((string) ($pdfContext['variant_label'] ?? ''));
        $priceModeLabel = trim((string) ($pdfContext['price_mode_label'] ?? ''));
        $partnerName = trim((string) ($pdfContext['partner_name'] ?? ''));
        $logoDataUri = null;
        $rows = array_values($evaluation['rows'] ?? []);
        $totals = array_values($evaluation['totals'] ?? []);
        $totalUsers = 0;
        $knowledgeUsers = 0;
        $frontlineUsers = 0;

        foreach ($requirements as $requirement) {
            $quantity = max(1, (int) ($requirement['quantity'] ?? 1));
            $totalUsers += $quantity;
            if (($requirement['audience'] ?? 'knowledge') === 'frontline') {
                $frontlineUsers += $quantity;
                continue;
            }

            $knowledgeUsers += $quantity;
        }

        $baseSkuCount = 0;
        $addonSkuCount = 0;
        foreach ($totals as $item) {
            if (($item['type_label'] ?? '') === 'Add-on') {
                $addonSkuCount++;
            } else {
                $baseSkuCount++;
            }
        }

        $grandTotal = $evaluation['grand_total'] ?? null;
        $annualBudget = $grandTotal !== null ? round((float) $grandTotal * 12, 2) : null;
        $topSkus = $totals;
        usort($topSkus, static function (array $left, array $right): int {
            $leftTotal = $left['line_total'] ?? null;
            $rightTotal = $right['line_total'] ?? null;

            if ($leftTotal === null && $rightTotal === null) {
                return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            }
            if ($leftTotal === null) {
                return 1;
            }
            if ($rightTotal === null) {
                return -1;
            }

            return ((float) $rightTotal <=> (float) $leftTotal);
        });
        $topSkus = array_slice($topSkus, 0, 3);

        $largestRow = null;
        foreach ($rows as $row) {
            if ($largestRow === null) {
                $largestRow = $row;
                continue;
            }

            $currentValue = $row['row_total'] ?? null;
            $largestValue = $largestRow['row_total'] ?? null;
            if ($largestValue === null && $currentValue !== null) {
                $largestRow = $row;
                continue;
            }
            if ($currentValue !== null && $largestValue !== null && (float) $currentValue > (float) $largestValue) {
                $largestRow = $row;
            }
        }

        $managementHighlights = [];
        $managementHighlights[] = sprintf(
            '%d Nutzer in %d Bedarfsgruppen wurden im Modell „%s“ ausgewertet.',
            $totalUsers,
            count($requirements),
            (string) ($billingContext['label'] ?? '1 Jahr · jährliche Zahlung')
        );

        if ($knowledgeUsers > 0 && $frontlineUsers > 0) {
            $managementHighlights[] = sprintf(
                'Es liegt ein Mischszenario aus %d Knowledge Workern und %d Frontline-Nutzern vor.',
                $knowledgeUsers,
                $frontlineUsers
            );
        } elseif ($frontlineUsers > 0) {
            $managementHighlights[] = sprintf('Der Fokus liegt auf %d Frontline-/Kiosk-Nutzern.', $frontlineUsers);
        } elseif ($knowledgeUsers > 0) {
            $managementHighlights[] = sprintf('Der Fokus liegt auf %d Knowledge Workern.', $knowledgeUsers);
        }

        if ($addonSkuCount > 0) {
            $managementHighlights[] = sprintf(
                '%d Basislizenzen werden durch %d zusätzliche Add-on-SKUs ergänzt.',
                $baseSkuCount,
                $addonSkuCount
            );
        } else {
            $managementHighlights[] = 'Die Empfehlung kommt ohne zusätzliche Add-ons aus und bleibt damit bewusst schlank.';
        }

        if ($annualBudget !== null) {
            $managementHighlights[] = 'Hochgerechnetes Jahresbudget im gewählten Modell: ' . $formatMoneyText($annualBudget) . '.';
        }

        if ($largestRow !== null) {
            $managementHighlights[] = 'Größter Bedarfsblock: ' . (string) ($largestRow['label'] ?? 'Bedarf') . ' mit ' . $formatMoneyText($largestRow['row_total'] ?? null) . ' pro Monat.';
        }

        $decisionSignals = [];
        if (!empty($evaluation['has_missing_prices'])) {
            $decisionSignals[] = 'Mindestens ein Preis ist im Katalog noch offen. Das Budget ist daher bewusst als vorläufig zu lesen.';
        } else {
            $decisionSignals[] = 'Alle empfohlenen Pakete sind aktuell bepreist; die Budgetwerte sind damit direkt entscheidungsfähig.';
        }

        if (!empty($billingContext['note'])) {
            $decisionSignals[] = (string) $billingContext['note'];
        }

        if ($pricingContext['label'] ?? '' !== '') {
            $decisionSignals[] = 'Preislogik dieser Auswertung: ' . (string) $pricingContext['label'] . '.';
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo $esc($settings['page_title'] ?? 'M365 Lizenz-Auswertung'); ?></title>
    <style>
        @page{size:A4 portrait;margin:7mm 7mm 8mm;}
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#0f172a;font-size:9.1px;line-height:1.4;margin:0;padding:0;background:#ffffff;}
        .m365lic-pdf{width:100%;max-width:none;margin:0;background:#ffffff;border:1px solid #dbe5f4;border-radius:8px;overflow:hidden;}
        .m365lic-pdf__header{background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 100%);color:#ffffff;padding:14px 14px 12px;}
        .header-brand-table{width:100%;border-collapse:collapse;}
        .header-brand-table td{border:none;vertical-align:top;padding:0;}
        .header-brand-table__logo{width:96px;padding-right:10px;}
        .header-brand-table__logo img{max-width:82px;max-height:40px;display:block;}
        .header-eyebrow{display:inline-block;padding:3px 9px;border-radius:999px;background:rgba(255,255,255,0.14);font-size:7.7px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;}
        .m365lic-pdf__header h1{margin:7px 0 4px;font-size:17px;line-height:1.08;}
        .m365lic-pdf__intro{margin:0;max-width:700px;font-size:9.2px;line-height:1.42;color:rgba(255,255,255,0.92);}
        .header-pill-wrap{margin-top:7px;}
        .header-pill{display:inline-block;padding:2px 7px;margin:0 4px 4px 0;border-radius:999px;background:rgba(255,255,255,0.12);font-size:7.3px;font-weight:700;}
        .m365lic-pdf__body{padding:10px 11px 8px;background:#f8fbff;}
        .m365lic-pdf__section{margin-bottom:10px;}
        .section-kicker{display:block;margin-bottom:4px;color:#2563eb;font-size:7.8px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;}
        .m365lic-pdf__section h2{margin:0 0 4px;font-size:12px;line-height:1.15;color:#0f172a;}
        .section-copy{margin:0 0 6px;color:#64748b;line-height:1.42;}
        .summary-grid,.executive-grid,.signal-grid,.result-grid,.license-grid{width:100%;border-collapse:separate;border-spacing:5px 5px;table-layout:fixed;}
        .summary-grid td,.executive-grid td,.signal-grid td,.result-grid td,.license-grid td{vertical-align:top;border:none;padding:0;}
        .summary-grid td{width:25%;}
        .executive-grid td,.signal-grid td{width:50%;}
        .license-grid td{width:50%;}
        .summary-card,.executive-card,.signal-card,.result-card,.license-card{border:1px solid #dbe5f4;border-radius:9px;background:#ffffff;overflow:hidden;page-break-inside:avoid;box-shadow:0 1px 6px rgba(15,23,42,0.04);}
        .summary-card{padding:7px 8px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);}
        .summary-card strong{display:block;margin-bottom:3px;color:#64748b;font-size:7.8px;letter-spacing:.06em;text-transform:uppercase;}
        .summary-card span{display:block;color:#0f172a;font-size:10.8px;font-weight:700;line-height:1.28;}
        .executive-card__head,.result-card__head,.license-card__head{padding:7px 8px;border-bottom:1px solid #e2e8f0;background:#ffffff;}
        .executive-card__body,.result-card__body,.license-card__body{padding:7px 8px;}
        .executive-card__title,.result-card__title,.license-card__title{margin:0;font-size:10.2px;font-weight:700;color:#0f172a;}
        .executive-card__body p,.result-card__body p,.license-card__body p{margin:0 0 5px;color:#475569;}
        .note-list,.sku-note-list,.feature-list{margin:0;padding-left:16px;color:#334155;}
        .note-list li,.sku-note-list li,.feature-list li{margin-bottom:3px;}
        .signal-card{padding:7px 8px;}
        .signal-card strong{display:block;margin-bottom:4px;color:#0f172a;font-size:9.8px;}
        .signal-card p{margin:0;color:#475569;}
        .signal-card--warning{background:#fff7ed;border-color:#fdba74;}
        .signal-card--info{background:#eff6ff;border-color:#bfdbfe;}
        .metric-list{width:100%;border-collapse:collapse;table-layout:fixed;}
        .metric-list td{padding:4px 0;border-bottom:1px solid #edf2f7;vertical-align:top;}
        .metric-list tr:last-child td{border-bottom:none;}
        .metric-label{color:#64748b;width:48%;}
        .metric-value{color:#0f172a;font-weight:700;text-align:right;}
        .chip{display:inline-block;padding:2px 6px;margin:0 4px 4px 0;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:7.2px;font-weight:700;}
        .chip--base{background:#dbeafe;color:#1d4ed8;}
        .chip--addon{background:#ecfeff;color:#0f766e;}
        .result-card{background:#ffffff;}
        .result-card__head{background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);}
        .result-meta{margin-top:4px;}
        .result-explanation{margin:0 0 6px;color:#475569;}
        .analysis-empty{border:1px dashed #cbd5e1;border-radius:8px;padding:8px;background:#ffffff;color:#64748b;}
        .m365-table{width:100%;border-collapse:collapse;table-layout:fixed;background:#ffffff;}
        .m365-table th,.m365-table td{padding:4px 5px;border-bottom:1px solid #dbe3ee;text-align:left;vertical-align:top;word-wrap:break-word;line-height:1.32;}
        .m365-table th{background:linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%);color:#475569;font-size:7.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;border-bottom:1px solid #cbd5e1;}
        .m365-table tbody tr:nth-child(even){background:#f8fbff;}
        .m365-table td.numeric{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap;}
        .muted{color:#64748b;}
        .warn{color:#b45309;font-weight:700;}
        .sku-summary-wrap{border:1px solid #dbe5f4;border-radius:9px;overflow:hidden;background:#ffffff;page-break-inside:avoid;}
        .sku-summary-footer{padding:7px 8px;border-top:1px solid #cbd5e1;background:linear-gradient(180deg,#eef4ff 0%,#e4ecff 100%);}
        .sku-summary-footer-table{width:100%;border-collapse:collapse;}
        .sku-summary-footer-table td{border:none;padding:0;}
        .sku-summary-footer__label{color:#334155;font-size:7.9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;}
        .sku-summary-footer__value{text-align:right;color:#0f172a;font-size:11px;font-weight:800;font-variant-numeric:tabular-nums;}
        .license-card__head{background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);}
        .license-card__chips{margin-top:4px;}
        .license-card__description{margin:0 0 5px;color:#64748b;}
        .feature-pill{display:inline-block;padding:2px 6px;margin:0 5px 5px 0;border-radius:999px;font-size:7.4px;font-weight:700;}
        .feature-pill--quick{background:#dbeafe;color:#1d4ed8;}
        .feature-pill--advanced{background:#ede9fe;color:#6d28d9;}
        .feature-pill--security{background:#fee2e2;color:#b91c1c;}
        .feature-pill--identity{background:#ede9fe;color:#6d28d9;}
        .feature-pill--productivity{background:#eff6ff;color:#1d4ed8;}
        .feature-pill--copilot{background:#cffafe;color:#0f766e;}
        .feature-box{border:1px solid #e2e8f0;border-radius:8px;padding:5px 6px;margin-bottom:4px;background:#ffffff;}
        .feature-box--quick{background:#f8fbff;border-color:#dbeafe;}
        .feature-box--advanced{background:#faf7ff;border-color:#e9d5ff;}
        .feature-box--security{background:#fef2f2;border-color:#fecaca;}
        .feature-box--identity{background:#f5f3ff;border-color:#ddd6fe;}
        .feature-box--productivity{background:#eff6ff;border-color:#bfdbfe;}
        .feature-box--copilot{background:#ecfeff;border-color:#a5f3fc;}
        .feature-box strong{display:block;margin-bottom:2px;color:#0f172a;}
        .feature-box span{display:block;color:#64748b;line-height:1.32;}
        .page-break{page-break-before:auto;}
        .footer{padding:7px 11px 8px;border-top:1px solid #e2e8f0;background:#ffffff;font-size:7.6px;color:#64748b;line-height:1.35;}
    </style>
</head>
<body>
<div class="m365lic-pdf">
    <div class="m365lic-pdf__header">
        <table class="header-brand-table">
            <tr>
                <?php if ($logoDataUri !== null): ?>
                <td class="header-brand-table__logo">
                    <img src="<?php echo $esc($logoDataUri); ?>" alt="Partnerlogo">
                </td>
                <?php endif; ?>
                <td>
                    <span class="header-eyebrow"><?php echo $esc($variantLabel !== '' ? $variantLabel : 'Microsoft 365 · Lizenzplanung'); ?></span>
                    <h1><?php echo $esc($settings['page_title'] ?? 'Microsoft 365 Lizenzberater'); ?></h1>
                    <p class="m365lic-pdf__intro"><?php echo $esc((string) ($settings['page_intro'] ?? 'Verdichtete Management-Auswertung mit Bedarfsbild, Budgetwirkung und Entscheidungsrelevanz je empfohlener Lizenz.')); ?></p>
                    <div class="header-pill-wrap">
                        <?php if ($partnerName !== ''): ?>
                        <span class="header-pill">Partner: <?php echo $esc($partnerName); ?></span>
                        <?php endif; ?>
                        <span class="header-pill">Zugriff: <?php echo $esc((string) ($pricingContext['label'] ?? 'Öffentlich')); ?></span>
                        <span class="header-pill">Abrechnung: <?php echo $esc((string) ($billingContext['short_label'] ?? ($billingContext['label'] ?? 'Jahr / jährlich'))); ?></span>
                        <?php if ($priceModeLabel !== ''): ?>
                        <span class="header-pill"><?php echo $esc($priceModeLabel); ?></span>
                        <?php endif; ?>
                        <span class="header-pill">Erstellt am <?php echo $esc($generatedAt); ?></span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="m365lic-pdf__body">
        <section class="m365lic-pdf__section">
            <span class="section-kicker">Management Summary</span>
            <h2>Überblick auf einen Blick</h2>
            <p class="section-copy">Das PDF orientiert sich optisch an der Seiten-Auswertung, verdichtet die Ergebnisse aber stärker für Budget- und Entscheidungsfragen.</p>
            <table class="summary-grid">
                <tr>
                    <td><div class="summary-card"><strong>Bedarfsgruppen</strong><span><?php echo (int) count($requirements); ?></span></div></td>
                    <td><div class="summary-card"><strong>Nutzer gesamt</strong><span><?php echo (int) $totalUsers; ?></span></div></td>
                    <td><div class="summary-card"><strong>Empfohlene SKUs</strong><span><?php echo (int) count($totals); ?></span></div></td>
                    <td><div class="summary-card"><strong>Budget / Jahr</strong><span><?php echo $annualBudget !== null ? $formatMoney($annualBudget) : '<span class="warn">teilweise offen</span>'; ?></span></div></td>
                </tr>
            </table>
        </section>

        <section class="m365lic-pdf__section">
            <span class="section-kicker">Entscheidungsbild</span>
            <h2>Executive Summary</h2>
            <table class="executive-grid">
                <tbody>
                    <tr>
                        <td>
                            <article class="executive-card">
                                <div class="executive-card__head">
                                    <h3 class="executive-card__title">Kernaussagen für Management & Einkauf</h3>
                                </div>
                                <div class="executive-card__body">
                                    <ul class="note-list">
                                        <?php foreach ($managementHighlights as $highlight): ?>
                                        <li><?php echo $esc((string) $highlight); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </article>
                        </td>
                        <td>
                            <article class="executive-card">
                                <div class="executive-card__head">
                                    <h3 class="executive-card__title">Budget & Struktur</h3>
                                </div>
                                <div class="executive-card__body">
                                    <table class="metric-list">
                                        <tr><td class="metric-label">Zugriff / Preisbereich</td><td class="metric-value"><?php echo $esc((string) ($pricingContext['label'] ?? 'Öffentlich')); ?></td></tr>
                                        <tr><td class="metric-label">Abrechnungsmodell</td><td class="metric-value"><?php echo $esc((string) ($billingContext['label'] ?? '1 Jahr · jährliche Zahlung')); ?></td></tr>
                                        <tr><td class="metric-label">Monatssumme</td><td class="metric-value"><?php echo $grandTotal !== null ? $formatMoney($grandTotal) : '<span class="warn">teilweise offen</span>'; ?></td></tr>
                                        <tr><td class="metric-label">Budget / Jahr</td><td class="metric-value"><?php echo $annualBudget !== null ? $formatMoney($annualBudget) : '<span class="warn">teilweise offen</span>'; ?></td></tr>
                                        <tr><td class="metric-label">Basislizenzen</td><td class="metric-value"><?php echo (int) $baseSkuCount; ?></td></tr>
                                        <tr><td class="metric-label">Add-ons</td><td class="metric-value"><?php echo (int) $addonSkuCount; ?></td></tr>
                                        <tr><td class="metric-label">Knowledge / Frontline</td><td class="metric-value"><?php echo (int) $knowledgeUsers; ?> / <?php echo (int) $frontlineUsers; ?></td></tr>
                                    </table>
                                </div>
                            </article>
                        </td>
                    </tr>
                </tbody>
            </table>

            <table class="signal-grid">
                <tbody>
                    <tr>
                        <td>
                            <article class="signal-card <?php echo !empty($evaluation['has_missing_prices']) ? 'signal-card--warning' : 'signal-card--info'; ?>">
                                <strong>Budgetsignal</strong>
                                <p><?php echo $esc((string) ($decisionSignals[0] ?? '')); ?></p>
                            </article>
                        </td>
                        <td>
                            <article class="signal-card signal-card--info">
                                <strong>Preis- und Vertragslogik</strong>
                                <p><?php echo $esc((string) ($decisionSignals[1] ?? ($decisionSignals[0] ?? ''))); ?></p>
                            </article>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <?php if ($topSkus !== []): ?>
        <section class="m365lic-pdf__section">
            <span class="section-kicker">Kostenfokus</span>
            <h2>Wichtigste Budgettreiber</h2>
            <table class="executive-grid">
                <tbody>
                    <tr>
                        <td>
                            <article class="executive-card">
                                <div class="executive-card__head">
                                    <h3 class="executive-card__title">Top-SKUs nach Monatsbudget</h3>
                                </div>
                                <div class="executive-card__body">
                                    <ul class="sku-note-list">
                                        <?php foreach ($topSkus as $index => $item): ?>
                                        <li>
                                            <strong><?php echo $esc((string) ($item['name'] ?? 'Lizenz')); ?></strong>
                                            – Rang <?php echo (int) ($index + 1); ?> · <?php echo $formatMoney($item['line_total'] ?? null); ?> pro Monat · <?php echo $esc((string) ($item['quantity_label'] ?? '')); ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </article>
                        </td>
                        <td>
                            <article class="executive-card">
                                <div class="executive-card__head">
                                    <h3 class="executive-card__title">Empfohlene Leseart</h3>
                                </div>
                                <div class="executive-card__body">
                                    <ul class="note-list">
                                        <li>Die größten Budgethebel liegen typischerweise in breit ausgerollten Basislizenzen und mandantenweiten Add-ons.</li>
                                        <li>Wo Add-ons stark ins Gewicht fallen, lohnt sich die Prüfung, ob Funktionen bereits in höheren Basisplänen enthalten sind.</li>
                                        <li>Für Einkauf und Leitung ist die folgende Bedarfsanalyse der beste Einstieg, um Kosten und fachlichen Nutzen je Gruppe gemeinsam zu betrachten.</li>
                                    </ul>
                                </div>
                            </article>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
        <?php endif; ?>

        <section class="m365lic-pdf__section">
            <span class="section-kicker">Gruppenweise Empfehlung</span>
            <h2>Bedarfsanalyse nach Benutzergruppen</h2>
            <?php if ($rows === []): ?>
            <div class="analysis-empty">Es liegen aktuell keine Bedarfsgruppen für die Auswertung vor.</div>
            <?php else: ?>
            <table class="result-grid">
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <article class="result-card">
                                <div class="result-card__head">
                                    <h3 class="result-card__title"><?php echo $esc((string) ($row['label'] ?? 'Bedarf')); ?></h3>
                                    <div class="result-meta">
                                        <span class="chip">Anzahl: <?php echo (int) ($row['quantity'] ?? 0); ?></span>
                                        <span class="chip"><?php echo $audienceLabel((string) ($row['audience'] ?? 'knowledge')); ?></span>
                                        <span class="chip">Monat: <?php echo ($row['row_total'] ?? null) !== null ? $formatMoney($row['row_total']) : '<span class="warn">offen</span>'; ?></span>
                                    </div>
                                </div>
                                <div class="result-card__body">
                                    <?php if ((string) ($row['explanation'] ?? '') !== ''): ?>
                                    <p class="result-explanation"><?php echo $esc((string) $row['explanation']); ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($row['items'])): ?>
                                    <table class="m365-table">
                                        <thead>
                                            <tr>
                                                <th>Lizenz</th>
                                                <th>Typ</th>
                                                <th>Abrechnung</th>
                                                <th>Preis</th>
                                                <th>Monat</th>
                                                <th>Jahr</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($row['items'] ?? []) as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $esc((string) ($item['name'] ?? '')); ?></strong><br>
                                                    <span class="muted"><?php echo $esc((string) ($item['pricing_basis_label'] ?? 'pro Benutzer')); ?> · <?php echo $esc((string) ($item['billing_cycle_label'] ?? '')); ?></span>
                                                    <?php if ((string) ($item['description'] ?? '') !== ''): ?>
                                                    <br><span class="muted"><?php echo $esc((string) $item['description']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="chip chip--<?php echo $esc($typeTone((string) ($item['type_label'] ?? 'Basislizenz'))); ?>"><?php echo $esc((string) ($item['type_label'] ?? '')); ?></span></td>
                                                <td><?php echo $esc((string) ($item['quantity_label'] ?? '')); ?></td>
                                                <td class="numeric"><?php echo $formatMoney($item['unit_price'] ?? null); ?></td>
                                                <td class="numeric"><?php echo $formatMoney($item['line_total'] ?? null); ?></td>
                                                <td class="numeric"><?php echo ($item['line_total'] ?? null) !== null ? $formatMoney(round((float) $item['line_total'] * 12, 2)) : '<span class="warn">offen</span>'; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php else: ?>
                                    <div class="analysis-empty">Für diese Bedarfsgruppe konnte keine Empfehlung gebildet werden.</div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <section class="m365lic-pdf__section">
            <span class="section-kicker">Lizenzübersicht</span>
            <h2>SKU-Gesamtsumme</h2>
            <p class="section-copy">Konsolidierte Sicht über alle empfohlenen SKUs – geeignet für Einkauf, Budgetfreigabe und Priorisierung.</p>
            <div class="sku-summary-wrap">
                <table class="m365-table">
                    <thead>
                        <tr>
                            <th>Anzahl</th>
                            <th>Lizenz</th>
                            <th>Typ</th>
                            <th>Einzelpreis</th>
                            <th>Monat</th>
                            <th>Jahr</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($totals as $item): ?>
                        <tr>
                            <td class="numeric"><?php echo (int) ($item['billing_quantity'] ?? $item['quantity'] ?? 0); ?></td>
                            <td>
                                <strong><?php echo $esc((string) ($item['name'] ?? '')); ?></strong><br>
                                <span class="muted"><?php echo $esc((string) ($item['pricing_basis_label'] ?? 'pro Benutzer')); ?> · <?php echo $esc((string) ($item['billing_cycle_label'] ?? '')); ?></span>
                                <?php if ((string) ($item['description'] ?? '') !== ''): ?>
                                <br><span class="muted"><?php echo htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="chip chip--<?php echo $esc($typeTone((string) ($item['type_label'] ?? 'Basislizenz'))); ?>"><?php echo $esc((string) ($item['type_label'] ?? '')); ?></span></td>
                            <td class="numeric"><?php echo $formatMoney($item['unit_price'] ?? null); ?></td>
                            <td class="numeric"><?php echo $formatMoney($item['line_total'] ?? null); ?></td>
                            <td class="numeric"><?php echo ($item['line_total'] ?? null) !== null ? $formatMoney(round((float) $item['line_total'] * 12, 2)) : '<span class="warn">offen</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="sku-summary-footer">
                    <table class="sku-summary-footer-table">
                        <tr>
                            <td class="sku-summary-footer__label">Gesamtkosten im gewählten Modell</td>
                            <td class="sku-summary-footer__value"><?php echo $grandTotal !== null ? $formatMoney($grandTotal) : '<span class="warn">teilweise offen</span>'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </section>

        <?php if (!empty($totals)): ?>
        <section class="m365lic-pdf__section">
            <span class="section-kicker">Lizenzprofil</span>
            <h2>Lizenz- und Nutzenprofile für Entscheider</h2>
            <p class="section-copy">Diese Detailansicht ergänzt die Budgetübersicht um fachliche Schwerpunkte, damit Fachbereich, IT und Management mit derselben Entscheidungsgrundlage arbeiten können.</p>
            <table class="license-grid">
                <tbody>
                <?php foreach (array_chunk($totals, 2) as $totalPair): ?>
                    <tr>
                        <?php for ($column = 0; $column < 2; $column++): ?>
                            <?php $item = $totalPair[$column] ?? null; ?>
                            <td>
                                <?php if (is_array($item)): ?>
                                <?php $featureDetails = is_array($item['feature_details'] ?? null) ? $item['feature_details'] : []; ?>
                                <article class="license-card">
                                    <div class="license-card__head">
                                        <h3 class="license-card__title"><?php echo $esc((string) ($item['name'] ?? 'Lizenz')); ?></h3>
                                        <div class="license-card__chips">
                                            <span class="chip chip--<?php echo $esc($typeTone((string) ($item['type_label'] ?? 'Basislizenz'))); ?>"><?php echo $esc((string) ($item['type_label'] ?? '')); ?></span>
                                            <span class="chip"><?php echo $esc((string) ($item['quantity_label'] ?? '')); ?></span>
                                            <span class="chip"><?php echo $formatMoney($item['line_total'] ?? null); ?> / Monat</span>
                                        </div>
                                    </div>
                                    <div class="license-card__body">
                                    <?php if ((string) ($item['description'] ?? '') !== ''): ?>
                                    <p class="license-card__description"><?php echo $esc((string) $item['description']); ?></p>
                                    <?php endif; ?>

                                    <?php if ((string) ($item['pricing_note'] ?? '') !== ''): ?>
                                    <p><strong>Preis-/Beschaffungshinweis:</strong> <?php echo $esc((string) $item['pricing_note']); ?></p>
                                    <?php endif; ?>

                                    <?php if ($featureDetails !== []): ?>
                                    <p style="margin:0 0 6px;color:#475569;"><strong>Funktionsschwerpunkte</strong></p>
                                    <div>
                                        <?php foreach ($featureDetails as $featureDetail): ?>
                                            <?php $groupLabel = (string) ($featureDetail['group_label'] ?? 'Feature'); ?>
                                            <?php $tone = $featureTone($groupLabel); ?>
                                            <div class="feature-box feature-box--<?php echo $esc($tone); ?>">
                                                <span class="feature-pill feature-pill--<?php echo $esc($tone); ?>"><?php echo $esc($groupLabel); ?></span>
                                                <strong><?php echo $esc((string) ($featureDetail['label'] ?? 'Feature')); ?></strong>
                                                <?php if ((string) ($featureDetail['description'] ?? '') !== ''): ?>
                                                <span><?php echo $esc((string) $featureDetail['description']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="analysis-empty">Für diese Lizenz sind aktuell keine Feature-Details gepflegt.</div>
                                    <?php endif; ?>
                                    </div>
                                </article>
                                <?php else: ?>
                                <div></div>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
    </div>
    <div class="footer">
        <?php echo $esc($settings['legal_note'] ?? ''); ?><br>
        <?php echo $esc($settings['pdf_footer'] ?? ''); ?>
    </div>
</div>
</body>
</html>
        <?php

        return (string) ob_get_clean();
    }

    public static function stream_pdf(string $html, string $filename): void
    {
        $normalizedFilename = self::normalize_pdf_filename($filename);

        if (class_exists('CMS\Services\PdfService')) {
            try {
                $pdfService = \CMS\Services\PdfService::getInstance();
                if ($pdfService->isAvailable()) {
                    $pdfService->setPaper('A4', 'portrait')->streamFromHtml($html, $normalizedFilename, false);
                    exit;
                }
            } catch (\Throwable) {
                // Fallback auf direkte Dompdf-Initialisierung.
            }
        }

        if (class_exists('CMS\VendorRegistry')) {
            \CMS\VendorRegistry::instance()->loadPackage('dompdf');
        }

        if (class_exists('Dompdf\Dompdf')) {
            $options = new \Dompdf\Options();
            $options->setIsRemoteEnabled(false);
            $options->setIsPhpEnabled(false);
            $options->setIsJavascriptEnabled(false);
            $options->set('defaultFont', 'DejaVu Sans');
            if (defined('ABSPATH')) {
                $options->setChroot(ABSPATH);
            }

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream($normalizedFilename, ['Attachment' => true]);
            exit;
        }

        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'PDF-Renderer ist aktuell nicht verfügbar. Bitte die 365CMS-Dompdf-Installation prüfen.';
        exit;
    }

    private static function normalize_pdf_filename(string $filename): string
    {
        $trimmed = trim($filename);
        if ($trimmed === '') {
            return 'export.pdf';
        }

        return str_ends_with(strtolower($trimmed), '.pdf') ? $trimmed : $trimmed . '.pdf';
    }

    /**
     * @return array<int|string,mixed>|string|int|float|bool|null
     */
    private static function sanitize_render_payload(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = self::sanitize_render_payload($item);
            }

            return $sanitized;
        }

        if (is_string($value)) {
            return trim(strip_tags($value));
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        return null;
    }

    private static function resolve_logo_data_uri(string $logoPath): ?string
    {
        $logoPath = trim($logoPath);
        if ($logoPath === '') {
            return null;
        }

        if (str_starts_with($logoPath, 'data:image/')) {
            return $logoPath;
        }

        if (!defined('ABSPATH')) {
            return null;
        }

        $pathOnly = (string) (parse_url($logoPath, PHP_URL_PATH) ?: $logoPath);
        $normalized = ltrim(str_replace(['\\', '//'], '/', $pathOnly), '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        $basePath = realpath(rtrim((string) ABSPATH, '/\\'));
        if ($basePath === false) {
            return null;
        }

        $absolutePath = realpath($basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized));

        if ($absolutePath === false) {
            return null;
        }

        $basePrefix = rtrim(str_replace('\\', '/', $basePath), '/');
        $resolvedPath = str_replace('\\', '/', $absolutePath);

        if ($resolvedPath !== $basePrefix && !str_starts_with($resolvedPath, $basePrefix . '/')) {
            return null;
        }

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $mimeType = function_exists('mime_content_type') ? (string) mime_content_type($absolutePath) : 'image/png';
        if (!str_starts_with($mimeType, 'image/')) {
            return null;
        }

        $binary = @file_get_contents($absolutePath);
        if ($binary === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
    }
}
