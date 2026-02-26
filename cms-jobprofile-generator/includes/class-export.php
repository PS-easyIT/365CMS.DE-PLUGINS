<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export-Funktionalität (HTML, JSON, CSV)
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Export
{
    private static ?self $instance = null;
    private \CMS\Database $db;
    private string $p;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->p  = $this->db->getPrefix();
    }

    /**
     * Gibt ein vollständiges Profil-Objekt inkl. aller Beziehungen zurück.
     */
    public function get_full_profile(int $id): ?array
    {
        $profile = CMS_JPG_Profiles::instance()->get($id);
        if (!$profile) {
            return null;
        }

        return [
            'profile'      => (array) $profile,
            'tasks'        => array_map(fn($r) => (array) $r, CMS_JPG_Profiles::instance()->get_tasks($id)),
            'requirements' => array_map(fn($r) => (array) $r, CMS_JPG_Profiles::instance()->get_requirements($id)),
            'skills'       => array_map(fn($r) => (array) $r, CMS_JPG_Profiles::instance()->get_skills($id)),
            'benefit_ids'  => CMS_JPG_Profiles::instance()->get_benefit_ids($id),
        ];
    }

    /**
     * Rendert ein Profil als HTML-String für die Previw-/Export-Funktion.
     */
    public function render_html(int $id): string
    {
        $data = $this->get_full_profile($id);
        if (!$data) {
            return '<p>Profil nicht gefunden.</p>';
        }

        $p    = $data['profile'];
        $esc  = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''));

        // Benefits laden
        $benefitIds = $data['benefit_ids'];
        $benefits   = [];
        if (!empty($benefitIds)) {
            $placeholders = implode(',', array_fill(0, count($benefitIds), '?'));
            $benefits = $this->db->get_results(
                "SELECT * FROM {$this->p}jpg_benefits WHERE id IN ({$placeholders}) ORDER BY group_name, sort_order",
                $benefitIds
            );
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $esc($p['title']); ?></title>
<style>
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;margin:0;padding:2rem;background:#f8fafc;}
  .jp-card{background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:2rem;max-width:860px;margin:0 auto;}
  h1{font-size:1.75rem;margin:0 0 .5rem;}
  .meta{color:#64748b;font-size:.9rem;margin-bottom:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;}
  .meta span{background:#f1f5f9;border-radius:4px;padding:.2rem .6rem;}
  h2{font-size:1.1rem;font-weight:700;border-bottom:2px solid #e2e8f0;padding-bottom:.4rem;margin:1.5rem 0 .75rem;}
  ul{margin:.5rem 0;padding-left:1.5rem;}
  li{margin:.35rem 0;}
  .pill{display:inline-block;background:#eff6ff;color:#1d4ed8;border-radius:20px;padding:.2rem .75rem;font-size:.82rem;margin:.2rem;}
  .badge-must{color:#065f46;background:#d1fae5;border-radius:4px;padding:.1rem .4rem;font-size:.78rem;margin-left:.4rem;}
  .badge-nice{color:#1e40af;background:#dbeafe;border-radius:4px;padding:.1rem .4rem;font-size:.78rem;margin-left:.4rem;}
  .skill-row{margin:.3rem 0;display:flex;align-items:center;gap:.75rem;}
  .skill-level{display:flex;gap:3px;}
  .skill-level span{width:12px;height:12px;border-radius:2px;background:#e2e8f0;}
  .skill-level span.filled{background:#3b82f6;}
  .benefit-icon{font-size:1.1rem;margin-right:.3rem;}
</style>
</head>
<body>
<div class="jp-card">
  <h1><?php echo $esc($p['title']); ?></h1>
  <div class="meta">
    <?php if (!empty($p['location'])): ?><span>📍 <?php echo $esc($p['location']); ?></span><?php endif; ?>
    <?php
    $types = ['fulltime'=>'Vollzeit','parttime'=>'Teilzeit','freelance'=>'Freiberuflich','internship'=>'Praktikum','mini'=>'Minijob'];
    echo '<span>' . $esc($types[$p['employment_type']] ?? $p['employment_type']) . '</span>';
    $remote = ['onsite'=>'Vor Ort','hybrid'=>'Hybrid','remote'=>'Remote'];
    echo '<span>' . $esc($remote[$p['remote_option']] ?? '') . '</span>';
    if (!empty($p['salary_min']) || !empty($p['salary_max'])):
        $sal = '';
        if (!empty($p['salary_min'])) $sal .= number_format((float)$p['salary_min'], 0, ',', '.');
        if (!empty($p['salary_max'])) $sal .= ' – ' . number_format((float)$p['salary_max'], 0, ',', '.') . '';
        echo '<span>💶 ' . $esc($sal) . ' ' . $esc($p['salary_currency'] ?? 'EUR') . '</span>';
    endif;
    ?>
  </div>

  <?php if (!empty($p['summary'])): ?>
  <p><?php echo nl2br($esc($p['summary'])); ?></p>
  <?php endif; ?>

  <?php if (!empty($data['tasks'])): ?>
  <h2>Aufgaben & Verantwortlichkeiten</h2>
  <ul>
    <?php foreach ($data['tasks'] as $task): ?>
    <li><?php echo $esc($task['task_text']); ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php
  $must = array_filter($data['requirements'], fn($r) => $r['req_type'] === 'must');
  $nice = array_filter($data['requirements'], fn($r) => $r['req_type'] === 'nice');
  if (!empty($must)): ?>
  <h2>Muss-Anforderungen</h2>
  <ul>
    <?php foreach ($must as $req): ?>
    <li><?php echo $esc($req['req_text']); ?> <span class="badge-must">Pflicht</span></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <?php if (!empty($nice)): ?>
  <h2>Wünschenswert</h2>
  <ul>
    <?php foreach ($nice as $req): ?>
    <li><?php echo $esc($req['req_text']); ?> <span class="badge-nice">Optional</span></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if (!empty($data['skills'])): ?>
  <h2>Skill-Matrix</h2>
  <?php $levels = ['basic'=>1,'intermediate'=>2,'advanced'=>3,'expert'=>4]; ?>
  <?php foreach ($data['skills'] as $sk): ?>
  <div class="skill-row">
    <span style="min-width:200px;"><?php echo $esc($sk['skill_name']); ?></span>
    <div class="skill-level">
      <?php for ($l = 1; $l <= 4; $l++): ?>
      <span class="<?php echo $l <= ($levels[$sk['level']] ?? 2) ? 'filled' : ''; ?>"></span>
      <?php endfor; ?>
    </div>
    <span style="font-size:.82rem;color:#64748b;"><?php echo $esc(ucfirst($sk['level'])); ?></span>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($benefits)): ?>
  <h2>Benefits</h2>
  <?php foreach ($benefits as $b): ?>
  <span class="pill"><span class="benefit-icon"><?php echo $b->icon; ?></span><?php echo $esc($b->title); ?></span>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($p['description'])): ?>
  <h2>Über uns / Weitere Informationen</h2>
  <?php echo $p['description']; // bereits durch SunEditor erzeugt – muss sanitized sein ?>
  <?php endif; ?>
</div>
</body>
</html>
        <?php
        return ob_get_clean() ?: '';
    }

    /**
     * Exportiert ein Profil als JSON.
     */
    public function export_json(int $id): string
    {
        $data = $this->get_full_profile($id);
        return $data ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
    }

    /**
     * Importiert ein Profil aus einem JSON-String.
     * Gibt die neue Profil-ID zurück oder 0 bei Fehler.
     */
    public function import_json(string $json, int $userId): int
    {
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['profile'])) {
            return 0;
        }

        $p = $data['profile'];
        $p['status']     = 'draft';
        $p['created_by'] = $userId;
        $p['updated_by'] = $userId;
        unset($p['id'], $p['slug'], $p['created_at'], $p['updated_at'], $p['published_at'], $p['archived_at']);

        $newId = CMS_JPG_Profiles::instance()->save($p);
        if ($newId <= 0) {
            return 0;
        }

        if (!empty($data['tasks'])) {
            CMS_JPG_Profiles::instance()->save_tasks(
                $newId,
                array_column($data['tasks'], 'task_text')
            );
        }
        if (!empty($data['requirements'])) {
            CMS_JPG_Profiles::instance()->save_requirements($newId, array_map(
                fn($r) => ['text' => $r['req_text'], 'type' => $r['req_type']],
                $data['requirements']
            ));
        }
        if (!empty($data['benefit_ids'])) {
            CMS_JPG_Profiles::instance()->save_benefits($newId, $data['benefit_ids']);
        }

        return $newId;
    }
}
