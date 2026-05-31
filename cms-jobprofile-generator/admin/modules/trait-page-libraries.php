<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Bibliotheken
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Libraries_Trait
{
    private const IMPORT_JSON_MAX_BYTES = 2_097_152; // 2 MB

    // ── 3. BIBLIOTHEKEN ──────────────────────────────────────────────────────

    public static function render_libraries(): void
    {
        self::check_access();

        $tab    = sanitize_key($_GET['tab'] ?? 'text-modules');
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_libraries_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_libraries_post($tab);
            }
        }

        $tabs = [
            'text-modules'      => 'Textbausteine',
            'skill-matrix'      => 'Skill-Matrix',
            'benefit-catalog'   => 'Benefit-Katalog',
            'requirement-items' => 'Anforderungs-Liste',
            'job-categories'    => 'Job-Kategorien',
            'import-export'     => 'Import/Export',
        ];

        // Daten je Tab
        $data = match ($tab) {
            'text-modules'      => ['items'   => CMS_JPG_TextModules::instance()->get_list(['limit' => 50])],
            'skill-matrix'      => ['grouped' => CMS_JPG_SkillMatrix::instance()->get_grouped()],
            'benefit-catalog'   => ['grouped' => CMS_JPG_BenefitsCatalog::instance()->get_grouped(false)],
            'requirement-items' => ['grouped' => class_exists('CMS_JPG_RequirementItems')
                ? CMS_JPG_RequirementItems::instance()->get_grouped()
                : []],
            'job-categories'    => ['items'   => CMS_JPG_JobCategories::instance()->get_all(false)],
            default             => [],
        };

        self::render_admin_view(
            'Bibliotheken',
            'jpg-libraries',
            JPG_DIR . 'admin/views/page-libraries.php',
            compact('tab', 'notice', 'error', 'tabs', 'data')
        );
    }

    /** @return array{string, string} */
    private static function handle_libraries_post(string $tab): array
    {
        $notice = '';
        $error  = '';
        $id     = (int) ($_POST['id'] ?? 0);
        $del    = (int) ($_POST['delete_id'] ?? 0);

        // Branchenpaket einspielen
        if (($_POST['_jpg_action'] ?? '') === 'seed_industry') {
            $industries = array_values(array_filter(array_map(
                'sanitize_key',
                (array) ($_POST['industries'] ?? [])
            )));
            if (!empty($industries)) {
                $count  = CMS_JPG_Installer::seed_industry_package($industries);
                $notice = "✅ {$count} Einträge für " . count($industries) . ' Branche(n) erfolgreich eingespielt.';
            } else {
                $error = 'Bitte mindestens eine Branche auswählen.';
            }
            return [$notice, $error];
        }

        switch ($tab) {
            case 'text-modules':
                if ($del > 0) {
                    CMS_JPG_TextModules::instance()->delete($del);
                    $notice = 'Textbaustein gelöscht.';
                    break;
                }
                if (empty(trim($_POST['title'] ?? ''))) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                CMS_JPG_TextModules::instance()->save([
                    'category' => sanitize_text_field($_POST['category'] ?? 'general'),
                    'title'    => sanitize_text_field($_POST['title'] ?? ''),
                    'content'  => $_POST['content'] ?? '',
                    'tags'     => sanitize_text_field($_POST['tags'] ?? ''),
                ], $id);
                $notice = 'Textbaustein gespeichert.';
                break;

            case 'skill-matrix':
                if ($del > 0) {
                    CMS_JPG_SkillMatrix::instance()->delete($del);
                    $notice = 'Skill gelöscht.';
                    break;
                }
                if (empty(trim($_POST['skill_name'] ?? ''))) {
                    $error = 'Skill-Name ist erforderlich.';
                    break;
                }
                CMS_JPG_SkillMatrix::instance()->save([
                    'group_name'  => sanitize_text_field($_POST['group_name'] ?? ''),
                    'skill_name'  => sanitize_text_field($_POST['skill_name'] ?? ''),
                    'description' => sanitize_text_field($_POST['description'] ?? ''),
                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                ], $id);
                $notice = 'Skill gespeichert.';
                break;

            case 'benefit-catalog':
                if ($del > 0) {
                    CMS_JPG_BenefitsCatalog::instance()->delete($del);
                    $notice = 'Benefit gelöscht.';
                    break;
                }
                if (empty(trim($_POST['title'] ?? ''))) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                CMS_JPG_BenefitsCatalog::instance()->save([
                    'group_name'  => sanitize_text_field($_POST['group_name'] ?? ''),
                    'title'       => sanitize_text_field($_POST['title'] ?? ''),
                    'description' => sanitize_text_field($_POST['description'] ?? ''),
                    'icon'        => sanitize_text_field($_POST['icon'] ?? '✓'),
                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                    'active'      => isset($_POST['active']) ? 1 : 0,
                ], $id);
                $notice = 'Benefit gespeichert.';
                break;

            case 'requirement-items':
                if ($del > 0) {
                    if (class_exists('CMS_JPG_RequirementItems')) {
                        CMS_JPG_RequirementItems::instance()->delete($del);
                    }
                    $notice = 'Eintrag gelöscht.';
                    break;
                }
                if (empty(trim($_POST['title'] ?? ''))) {
                    $error = 'Titel ist erforderlich.';
                    break;
                }
                if (class_exists('CMS_JPG_RequirementItems')) {
                    $reqType = sanitize_key($_POST['req_type'] ?? 'must');
                    $reqType = in_array($reqType, ['must','nice','optional'], true) ? $reqType : 'must';
                    CMS_JPG_RequirementItems::instance()->save([
                        'group_name'  => sanitize_text_field($_POST['group_name']  ?? ''),
                        'title'       => sanitize_text_field($_POST['title']       ?? ''),
                        'req_type'    => $reqType,
                        'description' => sanitize_text_field($_POST['description'] ?? ''),
                        'icon'        => sanitize_text_field($_POST['icon']        ?? ''),
                        'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                    ], $id);
                }
                $notice = 'Eintrag gespeichert.';
                break;

            case 'job-categories':
                if ($del > 0) {
                    CMS_JPG_JobCategories::instance()->delete($del);
                    $notice = 'Kategorie gelöscht.';
                    break;
                }
                if (empty(trim($_POST['name'] ?? ''))) {
                    $error = 'Name ist erforderlich.';
                    break;
                }
                CMS_JPG_JobCategories::instance()->save([
                    'name'        => sanitize_text_field($_POST['name'] ?? ''),
                    'description' => sanitize_text_field($_POST['description'] ?? ''),
                    'color'       => sanitize_text_field($_POST['color'] ?? '#3b82f6'),
                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                    'active'      => isset($_POST['active']) ? 1 : 0,
                ], $id);
                $notice = 'Kategorie gespeichert.';
                break;

            case 'import-export':
                if (!empty($_POST['import_json']) && !empty($_FILES['import_file']['tmp_name'])) {
                    $uploadError = (int) ($_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE);
                    if ($uploadError !== UPLOAD_ERR_OK) {
                        $error = 'Import fehlgeschlagen – Upload konnte nicht verarbeitet werden.';
                        break;
                    }
                    $tmpName = (string) ($_FILES['import_file']['tmp_name'] ?? '');
                    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                        $error = 'Import fehlgeschlagen – ungültige Upload-Datei.';
                        break;
                    }
                    $size = (int) ($_FILES['import_file']['size'] ?? 0);
                    if ($size <= 0 || $size > self::IMPORT_JSON_MAX_BYTES) {
                        $error = 'Import fehlgeschlagen – Datei ist leer oder zu groß (max. 2 MB).';
                        break;
                    }
                    $json = file_get_contents($tmpName);
                    if (!is_string($json) || trim($json) === '') {
                        $error = 'Import fehlgeschlagen – Datei konnte nicht gelesen werden.';
                        break;
                    }
                    json_decode($json, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $error = 'Import fehlgeschlagen – ungültiges JSON.';
                        break;
                    }
                    $auth  = \CMS\Auth::instance();
                    $uId   = method_exists($auth, 'getUserId') ? (int) $auth->getUserId() : 0;
                    $newId = CMS_JPG_Export::instance()->import_json($json, $uId);
                    if ($newId > 0) {
                        $notice = "Profil importiert (ID: {$newId}).";
                    } else {
                        $error = 'Import fehlgeschlagen – ungültiges JSON.';
                    }
                }
                break;
        }

        return [$notice, $error];
    }
}
