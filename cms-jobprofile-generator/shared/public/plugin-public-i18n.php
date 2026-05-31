<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Shared helper aus /plugins/shared/public laden (falls verfügbar).
$jpgSharedI18nFile = dirname(JPG_DIR) . '/shared/public/plugin-public-i18n.php';
if (is_file($jpgSharedI18nFile)) {
    require_once $jpgSharedI18nFile;
}

if (!function_exists('jpg_public_lang')) {
    function jpg_public_lang(): string
    {
        if (function_exists('cms_plugin_public_language')) {
            return cms_plugin_public_language();
        }

        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        return preg_match('#(^|/)en(/|$)#', $path) ? 'en' : 'de';
    }
}

if (!function_exists('jpg_public_path')) {
    function jpg_public_path(string $path = '', ?string $lang = null): string
    {
        $lang = $lang ?? jpg_public_lang();
        if (function_exists('cms_plugin_public_localized_path')) {
            return cms_plugin_public_localized_path($path, $lang);
        }

        $normalized = trim((string) preg_replace('#/+#', '/', $path), '/');
        if ($lang === 'en') {
            return '/en' . ($normalized !== '' ? '/' . $normalized : '');
        }

        return '/' . $normalized;
    }
}

if (!function_exists('jpg_public_catalog')) {
    /**
     * @return array<string, array{de:string,en:string}>
     */
    function jpg_public_catalog(): array
    {
        return [
            'jobs_open_positions'         => ['de' => 'Offene Stellen', 'en' => 'Open Positions'],
            'jobs_found_singular'         => ['de' => 'Stelle gefunden', 'en' => 'position found'],
            'jobs_found_plural'           => ['de' => 'Stellen gefunden', 'en' => 'positions found'],
            'jobs_none_found'             => ['de' => 'Keine Stellen gefunden', 'en' => 'No positions found'],
            'jobs_filter_company'         => ['de' => 'Unternehmen …', 'en' => 'Company …'],
            'jobs_filter_location'        => ['de' => 'Standort …', 'en' => 'Location …'],
            'jobs_filter_type_all'        => ['de' => 'Alle Arten', 'en' => 'All types'],
            'jobs_filter_category_all'    => ['de' => 'Alle Kategorien', 'en' => 'All categories'],
            'jobs_filter_remote_all'      => ['de' => 'Alle Arbeitsmodelle', 'en' => 'All work models'],
            'jobs_filter_salary_min'      => ['de' => 'Gehalt ab (€)', 'en' => 'Salary from (€)'],
            'jobs_filter_button'          => ['de' => 'Filtern', 'en' => 'Filter'],
            'jobs_filter_reset'           => ['de' => 'Zurücksetzen', 'en' => 'Reset'],
            'jobs_no_match_title'         => ['de' => 'Keine passenden Stellen gefunden', 'en' => 'No matching positions found'],
            'jobs_no_match_text'          => ['de' => 'Versuche es mit anderen Filtereinstellungen oder schau später wieder vorbei.', 'en' => 'Try different filters or check back later.'],
            'jobs_show_all'               => ['de' => 'Alle Stellen anzeigen', 'en' => 'Show all positions'],
            'jobs_details_apply'          => ['de' => 'Details & Bewerben', 'en' => 'Details & Apply'],
            'jobs_pagination_prev'        => ['de' => 'Zurück', 'en' => 'Previous'],
            'jobs_pagination_next'        => ['de' => 'Weiter', 'en' => 'Next'],
            'jobs_pagination_page'        => ['de' => 'Seite {page} von {pages}', 'en' => 'Page {page} of {pages}'],

            'type_fulltime'               => ['de' => 'Vollzeit', 'en' => 'Full-time'],
            'type_parttime'               => ['de' => 'Teilzeit', 'en' => 'Part-time'],
            'type_freelance'              => ['de' => 'Freiberuflich', 'en' => 'Freelance'],
            'type_internship'             => ['de' => 'Praktikum', 'en' => 'Internship'],
            'type_mini'                   => ['de' => 'Minijob', 'en' => 'Mini job'],
            'remote_onsite'               => ['de' => 'Vor Ort', 'en' => 'On-site'],
            'remote_hybrid'               => ['de' => 'Hybrid', 'en' => 'Hybrid'],
            'remote_remote'               => ['de' => 'Remote', 'en' => 'Remote'],
            'salary_from'                 => ['de' => 'ab', 'en' => 'from'],
            'salary_to'                   => ['de' => 'bis', 'en' => 'up to'],
            'salary_year'                 => ['de' => 'Jahr', 'en' => 'year'],
            'exp_junior'                  => ['de' => 'Junior', 'en' => 'Junior'],
            'exp_mid'                     => ['de' => 'Mid-Level', 'en' => 'Mid-level'],
            'exp_senior'                  => ['de' => 'Senior', 'en' => 'Senior'],
            'exp_lead'                    => ['de' => 'Lead', 'en' => 'Lead'],

            'single_about_role'           => ['de' => 'Über die Stelle', 'en' => 'About the role'],
            'single_tasks'                => ['de' => 'Ihre Aufgaben', 'en' => 'Your responsibilities'],
            'single_profile'              => ['de' => 'Ihr Profil', 'en' => 'Your profile'],
            'single_requirements'         => ['de' => 'Anforderungen', 'en' => 'Requirements'],
            'single_nice_to_have'         => ['de' => 'Von Vorteil', 'en' => 'Nice to have'],
            'single_benefits'             => ['de' => 'Das bieten wir', 'en' => 'What we offer'],
            'single_skills'               => ['de' => 'Gefragt', 'en' => 'Skills'],
            'single_team'                 => ['de' => 'Lerne dein Team kennen', 'en' => 'Meet your team'],
            'single_apply_now'            => ['de' => 'Jetzt bewerben', 'en' => 'Apply now'],

            'apply_title'                 => ['de' => 'Bewerbung: {title}', 'en' => 'Application: {title}'],
            'apply_close'                 => ['de' => 'Schließen', 'en' => 'Close'],
            'apply_name'                  => ['de' => 'Vollständiger Name', 'en' => 'Full name'],
            'apply_email'                 => ['de' => 'E-Mail-Adresse', 'en' => 'Email address'],
            'apply_phone_optional'        => ['de' => 'Telefon (optional)', 'en' => 'Phone (optional)'],
            'apply_password'              => ['de' => 'Passwort', 'en' => 'Password'],
            'apply_cover_letter'          => ['de' => 'Anschreiben', 'en' => 'Cover letter'],
            'apply_cv'                    => ['de' => 'Lebenslauf / CV (PDF oder Word, max. 5 MB)', 'en' => 'Resume / CV (PDF or Word, max. 5 MB)'],
            'apply_cv_hint'               => ['de' => 'Max. 5 MB, PDF oder Word', 'en' => 'Max. 5 MB, PDF or Word'],
            'apply_cancel'                => ['de' => 'Abbrechen', 'en' => 'Cancel'],
            'apply_submit'                => ['de' => 'Bewerbung absenden', 'en' => 'Submit application'],
            'apply_success_default'       => ['de' => 'Bewerbung eingereicht! Wir melden uns so schnell wie möglich.', 'en' => 'Application submitted! We will get back to you soon.'],
            'apply_register_and_apply'    => ['de' => 'Registrieren & Bewerben', 'en' => 'Register & Apply'],
            'apply_already_member'        => ['de' => 'Bereits Mitglied?', 'en' => 'Already a member?'],
            'apply_register_info'         => ['de' => 'Erstelle ein Konto, um dich zu bewerben und deine Bewerbungen zu verwalten.', 'en' => 'Create an account to apply and manage your applications.'],
            'apply_login_info'            => ['de' => 'Melde dich an, um dich auf diese Stelle zu bewerben.', 'en' => 'Sign in to apply for this position.'],
            'apply_login'                 => ['de' => 'Anmelden', 'en' => 'Sign in'],
            'apply_apply_data'            => ['de' => 'Bewerbungsdaten', 'en' => 'Application data'],
        ];
    }
}

if (!function_exists('jpg_public_t')) {
    /**
     * @param array<string,string|int> $replace
     */
    function jpg_public_t(string $key, array $replace = [], ?string $lang = null): string
    {
        $lang = $lang ?? jpg_public_lang();
        $catalog = jpg_public_catalog();
        $entry = $catalog[$key] ?? null;
        $text = $entry[$lang] ?? ($entry['de'] ?? $key);
        foreach ($replace as $rk => $rv) {
            $text = str_replace('{' . $rk . '}', (string) $rv, $text);
        }
        return $text;
    }
}
