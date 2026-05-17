<?php
/**
 * CMS M365 Tools – Annual vs. Monthly Commitment Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Commitment_Calculator
{
    /** @return array<string,mixed> */
    public static function default_input(): array
    {
        $assumptions = CMS_M365CALCULATOR_Catalog::commitment_assumptions();
        $defaults = is_array($assumptions['defaults'] ?? null) ? $assumptions['defaults'] : [];

        return [
            'plan' => (string) ($defaults['plan'] ?? 'm365-business-standard'),
            'users' => (int) ($defaults['users'] ?? 100),
            'volatile_percent' => (int) ($defaults['volatile_percent'] ?? 15),
            'monthly_growth_percent' => (float) ($defaults['monthly_growth_percent'] ?? 0),
            'stable_core_percent' => (int) ($defaults['stable_core_percent'] ?? 80),
            'horizon_months' => (int) ($defaults['horizon_months'] ?? 12),
            'channel' => (string) ($defaults['channel'] ?? 'csp'),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $assumptions = CMS_M365CALCULATOR_Catalog::commitment_assumptions();
        $limits = is_array($assumptions['limits'] ?? null) ? $assumptions['limits'] : [];
        $plans = self::plan_options();

        $plan = self::clean_key((string) ($source['plan'] ?? $input['plan']));
        $input['plan'] = isset($plans[$plan]) ? $plan : (string) $input['plan'];
        $input['users'] = self::clamp_int((int) ($source['users'] ?? $input['users']), (int) ($limits['users_min'] ?? 1), (int) ($limits['users_max'] ?? 500000));
        $input['volatile_percent'] = self::clamp_int((int) ($source['volatile_percent'] ?? $input['volatile_percent']), (int) ($limits['volatile_percent_min'] ?? 0), (int) ($limits['volatile_percent_max'] ?? 80));
        $input['monthly_growth_percent'] = self::clamp_float((float) ($source['monthly_growth_percent'] ?? $input['monthly_growth_percent']), (float) ($limits['monthly_growth_percent_min'] ?? -10), (float) ($limits['monthly_growth_percent_max'] ?? 15));
        $input['stable_core_percent'] = self::clamp_int((int) ($source['stable_core_percent'] ?? $input['stable_core_percent']), (int) ($limits['stable_core_percent_min'] ?? 0), (int) ($limits['stable_core_percent_max'] ?? 100));

        $horizonOptions = array_map('intval', is_array($limits['horizon_options'] ?? null) ? $limits['horizon_options'] : [12, 24, 36]);
        $horizon = (int) ($source['horizon_months'] ?? $input['horizon_months']);
        $input['horizon_months'] = in_array($horizon, $horizonOptions, true) ? $horizon : 12;
        $input['channel'] = self::enum((string) ($source['channel'] ?? $input['channel']), ['csp', 'mca', 'ea'], 'csp');

        return $input;
    }

    /** @return array<string,string> */
    public static function plan_options(): array
    {
        $options = [];
        foreach (self::plans_index() as $slug => $plan) {
            $options[$slug] = (string) ($plan['name'] ?? $slug);
        }

        return $options;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $plans = self::plans_index();
        $plan = is_array($plans[(string) ($input['plan'] ?? '')] ?? null) ? $plans[(string) $input['plan']] : reset($plans);
        $plan = is_array($plan) ? $plan : [];
        $assumptions = CMS_M365CALCULATOR_Catalog::commitment_assumptions();
        $channelNotes = CMS_M365CALCULATOR_Catalog::commitment_channel_notes();
        $labels = is_array($assumptions['labels'] ?? null) ? $assumptions['labels'] : [];
        $months = self::build_months($input);
        $models = [
            self::monthly_model($plan, $months, (string) ($labels['monthly_flex'] ?? 'Monatslaufzeit')),
            self::annual_model($plan, $months, 'annual_monthly', (string) ($labels['annual_monthly'] ?? 'Jahreslaufzeit, monatlich abgerechnet')),
            self::annual_model($plan, $months, 'annual_prepaid', (string) ($labels['annual_prepaid'] ?? 'Jahreslaufzeit, jährlich abgerechnet')),
            self::split_model($input, $plan, $months, (string) ($labels['split_commitment'] ?? 'Stabiler Kern jährlich, variable Nutzer monatlich')),
        ];
        $models = self::add_savings($models);
        $recommendation = self::recommend($input, $models, $assumptions);
        $pricing = CMS_M365CALCULATOR_Catalog::commitment_pricing();

        return [
            'input' => $input,
            'plan' => $plan,
            'months' => $months,
            'models' => $models,
            'chart' => self::chart($models),
            'recommendation' => $recommendation,
            'best_model' => (string) ($recommendation['best_model'] ?? ''),
            'assumption_notes' => is_array($assumptions['notes'] ?? null) ? $assumptions['notes'] : [],
            'channel' => self::channel_block((string) ($input['channel'] ?? 'csp'), $channelNotes),
            'sources' => array_values(array_unique(array_merge(
                is_array($channelNotes['sources'] ?? null) ? $channelNotes['sources'] : [],
                is_array($pricing['meta']['sources'] ?? null) ? $pricing['meta']['sources'] : []
            ))),
            'disclaimer' => (string) ($channelNotes['disclaimer'] ?? ''),
        ];
    }

    public static function render_commitment_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-commitment-calculator.php';
    }

    /** @return array<string,array<string,mixed>> */
    private static function plans_index(): array
    {
        $pricing = CMS_M365CALCULATOR_Catalog::commitment_pricing();
        $plans = is_array($pricing['plans'] ?? null) ? $pricing['plans'] : [];
        $indexed = [];
        foreach ($plans as $plan) {
            if (is_array($plan) && !empty($plan['slug'])) {
                $indexed[(string) $plan['slug']] = $plan;
            }
        }

        return $indexed;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<int,array<string,mixed>>
     */
    private static function build_months(array $input): array
    {
        $months = [];
        $users = max(1, (int) ($input['users'] ?? 1));
        $growth = (float) ($input['monthly_growth_percent'] ?? 0) / 100;
        $volatility = max(0, min(80, (int) ($input['volatile_percent'] ?? 0))) / 100;
        $horizon = max(12, (int) ($input['horizon_months'] ?? 12));

        for ($month = 1; $month <= $horizon; $month++) {
            $projected = max(1, (int) round($users * ((1 + $growth) ** ($month - 1))));
            $inactiveVariable = (int) round($projected * $volatility * 0.5);
            $active = max(1, $projected - $inactiveVariable);
            $months[] = [
                'month' => $month,
                'projected_seats' => $projected,
                'active_seats' => $active,
                'variable_seats' => max(0, $projected - $active),
            ];
        }

        return $months;
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<int,array<string,mixed>> $months
     * @return array<string,mixed>
     */
    private static function monthly_model(array $plan, array $months, string $label): array
    {
        $price = (float) ($plan['monthly_price_month'] ?? 0);
        $total = 0.0;
        $seatMonths = 0;
        foreach ($months as $month) {
            $active = (int) ($month['active_seats'] ?? 0);
            $seatMonths += $active;
            $total += $active * $price;
        }

        return self::model('monthly_flex', $label, 'P1M / monatliche Abrechnung', $total, $months, $seatMonths, 0, $price, $price * (int) ($months[0]['active_seats'] ?? 1));
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<int,array<string,mixed>> $months
     * @return array<string,mixed>
     */
    private static function annual_model(array $plan, array $months, string $key, string $label): array
    {
        $price = $key === 'annual_monthly'
            ? (float) ($plan['annual_monthly_price_month'] ?? ($plan['annual_price_month'] ?? 0))
            : (float) ($plan['annual_price_month'] ?? 0);
        $total = 0.0;
        $committed = 0;
        $seatMonths = 0;
        $unusedSeatMonths = 0;
        $firstInvoice = 0.0;

        foreach ($months as $month) {
            $monthNumber = (int) ($month['month'] ?? 1);
            $projected = (int) ($month['projected_seats'] ?? 1);
            $active = (int) ($month['active_seats'] ?? $projected);
            if (($monthNumber - 1) % 12 === 0) {
                $committed = $projected;
                if ($key === 'annual_prepaid' && $firstInvoice <= 0) {
                    $firstInvoice = $committed * $price * 12;
                }
            }
            $committed = max($committed, $projected);
            $seatMonths += $committed;
            $unusedSeatMonths += max(0, $committed - $active);
            $total += $committed * $price;
        }

        $termLabel = $key === 'annual_prepaid' ? 'P1Y / jährliche Abrechnung' : 'P1Y / monatliche Abrechnung';
        $invoice = $key === 'annual_prepaid' ? $firstInvoice : $price * max(1, $committed);

        return self::model($key, $label, $termLabel, $total, $months, $seatMonths, $unusedSeatMonths, $price, $invoice);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $plan
     * @param array<int,array<string,mixed>> $months
     * @return array<string,mixed>
     */
    private static function split_model(array $input, array $plan, array $months, string $label): array
    {
        $annualPrice = (float) ($plan['annual_price_month'] ?? 0);
        $monthlyPrice = (float) ($plan['monthly_price_month'] ?? 0);
        $stablePercent = max(0, min(100, (int) ($input['stable_core_percent'] ?? 80))) / 100;
        $total = 0.0;
        $seatMonths = 0;
        $unusedSeatMonths = 0;
        $stableCommit = 0;

        foreach ($months as $month) {
            $monthNumber = (int) ($month['month'] ?? 1);
            $projected = (int) ($month['projected_seats'] ?? 1);
            $active = (int) ($month['active_seats'] ?? $projected);
            if (($monthNumber - 1) % 12 === 0) {
                $stableCommit = max(1, (int) floor($projected * $stablePercent));
            }
            $annualSeats = min($stableCommit, $projected);
            $monthlySeats = max(0, $active - $stableCommit);
            $unusedSeatMonths += max(0, $annualSeats - min($active, $annualSeats));
            $seatMonths += $annualSeats + $monthlySeats;
            $total += ($annualSeats * $annualPrice) + ($monthlySeats * $monthlyPrice);
        }

        $firstActive = (int) ($months[0]['active_seats'] ?? 1);
        $firstInvoice = ($stableCommit * $annualPrice) + (max(0, $firstActive - $stableCommit) * $monthlyPrice);

        return self::model('split_commitment', $label, 'P1Y stabiler Kern + P1M variable Nutzer', $total, $months, $seatMonths, $unusedSeatMonths, $annualPrice, $firstInvoice);
    }

    /**
     * @param array<int,array<string,mixed>> $months
     * @return array<string,mixed>
     */
    private static function model(string $key, string $label, string $termLabel, float $total, array $months, int $seatMonths, int $unusedSeatMonths, float $unitPrice, float $firstInvoice): array
    {
        $horizon = max(1, count($months));

        return [
            'key' => $key,
            'label' => $label,
            'term_label' => $termLabel,
            'total' => round($total, 2),
            'yearly_equivalent' => round($total / $horizon * 12, 2),
            'average_monthly' => round($total / $horizon, 2),
            'seat_months' => $seatMonths,
            'unused_seat_months' => $unusedSeatMonths,
            'unit_price' => $unitPrice,
            'first_invoice' => round($firstInvoice, 2),
            'savings_vs_monthly' => 0.0,
            'savings_percent_vs_monthly' => 0.0,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $models
     * @return array<int,array<string,mixed>>
     */
    private static function add_savings(array $models): array
    {
        $monthly = 0.0;
        foreach ($models as $model) {
            if ((string) ($model['key'] ?? '') === 'monthly_flex') {
                $monthly = (float) ($model['total'] ?? 0);
                break;
            }
        }

        foreach ($models as $index => $model) {
            $total = (float) ($model['total'] ?? 0);
            $savings = $monthly - $total;
            $models[$index]['savings_vs_monthly'] = round($savings, 2);
            $models[$index]['savings_percent_vs_monthly'] = $monthly > 0 ? round(($savings / $monthly) * 100, 1) : 0.0;
        }

        return $models;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<int,array<string,mixed>> $models
     * @param array<string,mixed> $assumptions
     * @return array<string,mixed>
     */
    private static function recommend(array $input, array $models, array $assumptions): array
    {
        $thresholds = is_array($assumptions['recommendation_thresholds'] ?? null) ? $assumptions['recommendation_thresholds'] : [];
        $meaningful = (float) ($thresholds['meaningful_savings_percent'] ?? 5);
        $highVolatility = (int) ($thresholds['high_volatility_percent'] ?? 20);
        $volatility = (int) ($input['volatile_percent'] ?? 0);
        $best = null;

        foreach ($models as $model) {
            if ($best === null || (float) ($model['total'] ?? 0) < (float) ($best['total'] ?? PHP_FLOAT_MAX)) {
                $best = $model;
            }
        }
        $best = is_array($best) ? $best : [];
        $bestKey = (string) ($best['key'] ?? 'monthly_flex');
        $savingsPercent = (float) ($best['savings_percent_vs_monthly'] ?? 0);
        $title = 'Monatslaufzeit als sichere Flex-Variante prüfen';
        $tone = 'warning';
        $summary = 'Die flexible Monatslaufzeit vermeidet Überbindung. Das ist besonders relevant, wenn Nutzerzahlen stark schwanken oder Abgänge erwartet werden.';

        if ($bestKey === 'split_commitment') {
            $title = 'Split-Strategie empfohlen';
            $tone = 'success';
            $summary = 'Der stabile Nutzerkern wird jährlich gebunden, volatile Seats bleiben monatlich flexibel. Das reduziert Overcommitment ohne komplett auf Commitment-Rabatt zu verzichten.';
        } elseif (in_array($bestKey, ['annual_monthly', 'annual_prepaid'], true) && $savingsPercent >= $meaningful && $volatility < $highVolatility) {
            $title = 'Jahreslaufzeit wirtschaftlich sinnvoll';
            $tone = 'success';
            $summary = 'Die jährliche Bindung spart deutlich gegenüber Monatslaufzeit. Monatliche Abrechnung glättet Cashflow, jährliche Abrechnung verändert vor allem die Liquidität.';
        } elseif ($bestKey === 'monthly_flex') {
            $title = 'Flexibilität gewinnt gegen Commitment';
            $tone = 'warning';
            $summary = 'Bei der gepflegten Volatilität ist der Flexibilitätswert höher als der Commitment-Vorteil. Reduktionen nach sieben Tagen sind in NCE meist nicht mehr möglich.';
        }

        return [
            'title' => $title,
            'tone' => $tone,
            'summary' => $summary,
            'best_model' => $bestKey,
            'best_total' => (float) ($best['total'] ?? 0),
            'best_savings_percent' => $savingsPercent,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $models
     * @return array<int,array<string,mixed>>
     */
    private static function chart(array $models): array
    {
        $max = max(1.0, ...array_map(static fn(array $model): float => (float) ($model['total'] ?? 0), $models));
        $chart = [];
        foreach ($models as $model) {
            $chart[] = [
                'label' => (string) ($model['label'] ?? ''),
                'value' => (float) ($model['total'] ?? 0),
                'width' => round(((float) ($model['total'] ?? 0) / $max) * 100, 1),
                'key' => (string) ($model['key'] ?? ''),
            ];
        }

        return $chart;
    }

    /**
     * @param array<string,mixed> $notes
     * @return array<string,mixed>
     */
    private static function channel_block(string $channel, array $notes): array
    {
        $channels = is_array($notes['channels'] ?? null) ? $notes['channels'] : [];
        return is_array($channels[$channel] ?? null) ? $channels[$channel] : ['label' => strtoupper($channel), 'notes' => []];
    }

    private static function clamp_int(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private static function clamp_float(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }
}
