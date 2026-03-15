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
     */
    public static function render_html(array $evaluation, array $requirements, array $settings, array $pricingContext, array $billingContext): string
    {
        $esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $currency = $settings['default_currency'] ?? 'USD';
        $generatedAt = date('d.m.Y H:i');

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo $esc($settings['page_title'] ?? 'M365 Lizenz-Auswertung'); ?></title>
    <style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#1e293b;font-size:12px;line-height:1.45;margin:0;padding:24px;background:#f8fafc;}
        .m365lic-pdf{max-width:980px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
        .m365lic-pdf__header{background:#0f172a;color:#fff;padding:24px 28px;}
        .m365lic-pdf__header h1{margin:0 0 6px;font-size:24px;}
        .m365lic-pdf__meta{font-size:11px;opacity:.9;}
        .m365lic-pdf__body{padding:24px 28px;}
        .m365lic-pdf__section{margin-bottom:24px;}
        .m365lic-pdf__section h2{font-size:16px;margin:0 0 10px;color:#0f172a;border-bottom:2px solid #e2e8f0;padding-bottom:6px;}
        .m365lic-pdf__grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:12px;}
        .m365lic-pdf__card{border:1px solid #e2e8f0;border-radius:8px;padding:12px;background:#f8fafc;}
        .m365lic-pdf__card strong{display:block;font-size:11px;text-transform:uppercase;color:#475569;margin-bottom:4px;}
        table{width:100%;border-collapse:collapse;margin-top:10px;}
        th,td{border:1px solid #e2e8f0;padding:8px 10px;text-align:left;vertical-align:top;}
        th{background:#f1f5f9;font-size:11px;text-transform:uppercase;color:#475569;}
        .muted{color:#64748b;}
        .warn{color:#b45309;font-weight:700;}
        .footer{padding:16px 28px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:10px;color:#64748b;}
        ul{margin:6px 0 0 18px;padding:0;}
        li{margin-bottom:4px;}
    </style>
</head>
<body>
<div class="m365lic-pdf">
    <div class="m365lic-pdf__header">
        <h1><?php echo $esc($settings['page_title'] ?? 'Microsoft 365 Lizenzberater'); ?></h1>
        <div class="m365lic-pdf__meta">
            Zugriff: <?php echo $esc((string) ($pricingContext['label'] ?? 'Öffentlich')); ?> · Abrechnung: <?php echo $esc((string) ($billingContext['label'] ?? '1 Jahr · jährliche Zahlung')); ?> · Erstellt am <?php echo $esc($generatedAt); ?>
        </div>
    </div>
    <div class="m365lic-pdf__body">
        <section class="m365lic-pdf__section">
            <h2>Überblick</h2>
            <div class="m365lic-pdf__grid">
                <div class="m365lic-pdf__card">
                    <strong>Bedarfsgruppen</strong>
                    <?php echo (int) count($requirements); ?>
                </div>
                <div class="m365lic-pdf__card">
                    <strong>Empfohlene SKUs</strong>
                    <?php echo (int) count($evaluation['totals'] ?? []); ?>
                </div>
                <div class="m365lic-pdf__card">
                    <strong>Abrechnung</strong>
                    <?php echo $esc((string) ($billingContext['short_label'] ?? 'Jahr / jährlich')); ?>
                </div>
                <div class="m365lic-pdf__card">
                    <strong>Monatssumme</strong>
                    <?php if (($evaluation['grand_total'] ?? null) !== null): ?>
                        <?php echo number_format((float) $evaluation['grand_total'], 2, ',', '.'); ?> <?php echo $esc($currency); ?>
                    <?php else: ?>
                        <span class="warn">Teilweise ohne Preis</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="m365lic-pdf__section">
            <h2>Bedarfsanalyse</h2>
            <?php foreach (($evaluation['rows'] ?? []) as $row): ?>
            <div class="m365lic-pdf__card" style="margin-bottom:12px;">
                <strong><?php echo $esc((string) ($row['label'] ?? 'Bedarf')); ?></strong>
                <div class="muted">
                    Anzahl: <?php echo (int) ($row['quantity'] ?? 0); ?> · Zielgruppe: <?php echo $esc((string) ($row['audience'] ?? 'knowledge')); ?>
                </div>
                <ul>
                    <?php foreach (($row['items'] ?? []) as $item): ?>
                    <li>
                        <?php echo $esc((string) ($item['name'] ?? '')); ?>
                        (<?php echo $esc((string) ($item['pricing_basis_label'] ?? 'pro Benutzer')); ?>, <?php echo $esc((string) ($item['quantity_label'] ?? '')); ?>)
                        <?php if (($item['line_total'] ?? null) !== null): ?>
                            – <?php echo number_format((float) $item['line_total'], 2, ',', '.'); ?> <?php echo $esc($currency); ?>
                        <?php else: ?>
                            – <span class="warn">Preis offen</span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top:8px;"><?php echo $esc((string) ($row['explanation'] ?? '')); ?></div>
            </div>
            <?php endforeach; ?>
        </section>

        <section class="m365lic-pdf__section">
            <h2>SKU-Gesamtsumme</h2>
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Typ</th>
                        <th>Abrechnung</th>
                        <th>Einzelpreis</th>
                        <th>Gesamt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($evaluation['totals'] ?? []) as $item): ?>
                    <tr>
                        <td>
                            <?php echo $esc((string) ($item['name'] ?? '')); ?><br>
                            <span class="muted"><?php echo $esc((string) ($item['pricing_basis_label'] ?? 'pro Benutzer')); ?></span>
                        </td>
                        <td><?php echo $esc((string) ($item['type_label'] ?? '')); ?></td>
                        <td><?php echo $esc((string) ($item['quantity_label'] ?? '')); ?></td>
                        <td>
                            <?php if (($item['unit_price'] ?? null) !== null): ?>
                                <?php echo number_format((float) $item['unit_price'], 2, ',', '.'); ?> <?php echo $esc($currency); ?>
                            <?php else: ?>
                                <span class="warn">offen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($item['line_total'] ?? null) !== null): ?>
                                <?php echo number_format((float) $item['line_total'], 2, ',', '.'); ?> <?php echo $esc($currency); ?>
                            <?php else: ?>
                                <span class="warn">offen</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
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
        if (class_exists('Dompdf\Dompdf')) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream($filename, ['Attachment' => true]);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        echo $html;
        exit;
    }
}
