<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Whitelabel-Einzelansicht (Standalone-Seite)
 *
 * Rendert ohne CMS-Theme ein eigenständiges HTML5-Dokument.
 * Verwendet Custom-Branding-Farben aus `jpg_settings` und bindet public.css inline ein.
 *
 * @var object        $profile
 * @var array<object> $tasks
 * @var array<object> $requirements
 * @var array         $benefits
 * @var array<object> $skills
 * @var string        $company
 * @var string        $jsonld
 * @var string        $brandingCss
 * @var string        $applyCsrf   CSRF-Token für Bewerbungsformular
 *
 * @since   0.0.1
 * @package CMS_JobProfileGenerator
 */

$esc = function (string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
};

$pageTitle = $esc($profile->title) . ($company ? ' – ' . $esc($company) : '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $esc(mb_substr(strip_tags($profile->summary ?? ''), 0, 160)); ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $esc(mb_substr(strip_tags($profile->summary ?? ''), 0, 200)); ?>">
    <meta property="og:url" content="<?php echo $esc(SITE_URL . '/career/' . $profile->slug); ?>">

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo $esc(SITE_URL . '/career/' . $profile->slug); ?>">

    <!-- public.css inline (Whitelabel → keine externe Abhängigkeit) -->
    <style>
    <?php
    $cssPath = JPG_DIR . 'assets/css/public.css';
    if (file_exists($cssPath)) {
        echo file_get_contents($cssPath);
    }
    ?>
    /* Branding-Overrides */
    <?php echo $brandingCss ?? ''; ?>

    /* Whitelabel-spezifische Ergänzungen */
    body {
        margin: 0;
        padding: 0;
        background: #f1f5f9;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #1e293b;
    }
    .wl-container {
        max-width: 960px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .wl-footer {
        text-align: center;
        padding: 2rem 0;
        color: #94a3b8;
        font-size: .82rem;
    }
    .wl-footer a { color: #64748b; text-decoration: none; }
    .wl-footer a:hover { text-decoration: underline; }
    @media print {
        body { background: #fff; }
        .wl-container { margin: 0; max-width: 100%; }
        .wl-footer, .jpg-btn-apply { display: none; }
    }
    </style>

    <!-- JSON-LD -->
    <?php if (!empty($jsonld)): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>
</head>
<body>

<div class="wl-container">

    <?php if (!empty($company)): ?>
    <div style="margin-bottom:1rem;color:#64748b;font-size:.9rem;">
        <?php echo $esc($company); ?>
    </div>
    <?php endif; ?>

    <div class="jpg-public">
        <div class="jpg-job-card">

            <!-- Header -->
            <div class="jpg-job-header">
                <div>
                    <h1><?php echo $esc($profile->title); ?></h1>
                    <div class="jpg-job-meta">
                        <?php if (!empty($profile->location)): ?>
                        <span>📍 <?php echo $esc($profile->location); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($profile->employment_type)): ?>
                        <span>💼 <?php echo $esc(match ($profile->employment_type) {
                            'fulltime'  => 'Vollzeit',
                            'parttime'  => 'Teilzeit',
                            'contract'  => 'Freelance',
                            'temporary' => 'Befristet',
                            'intern'    => 'Praktikum',
                            'minijob'   => 'Minijob',
                            default     => $profile->employment_type,
                        }); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($profile->experience_level)): ?>
                        <span>⭐ <?php echo $esc(match ($profile->experience_level) {
                            'junior' => 'Junior',
                            'mid'    => 'Mid-Level',
                            'senior' => 'Senior',
                            'lead'   => 'Lead',
                            default  => $profile->experience_level,
                        }); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($profile->salary_min) || !empty($profile->salary_max)): ?>
                <div>
                    <span class="jpg-salary-badge">
                        💰 <?php
                        if ($profile->salary_min && $profile->salary_max) {
                            echo number_format((float) $profile->salary_min, 0, ',', '.') . ' – '
                               . number_format((float) $profile->salary_max, 0, ',', '.') . ' €';
                        } elseif ($profile->salary_min) {
                            echo 'ab ' . number_format((float) $profile->salary_min, 0, ',', '.') . ' €';
                        } else {
                            echo 'bis ' . number_format((float) $profile->salary_max, 0, ',', '.') . ' €';
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="jpg-job-body">

                <?php if (!empty($profile->summary)): ?>
                <div class="jpg-section">
                    <h2>Über die Stelle</h2>
                    <p><?php echo nl2br($esc($profile->summary)); ?></p>
                </div>
                <?php endif; ?>

                <div class="jpg-job-two-col">
                    <div>
                        <!-- Aufgaben -->
                        <?php if (!empty($tasks)): ?>
                        <div class="jpg-section">
                            <h2>Ihre Aufgaben</h2>
                            <ul>
                            <?php foreach ($tasks as $task): ?>
                                <li><?php echo $esc($task->task_text); ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Anforderungen -->
                        <?php if (!empty($requirements)): ?>
                        <div class="jpg-section">
                            <h2>Ihr Profil</h2>
                            <?php
                            $must = array_filter($requirements, fn($r) => ($r->type ?? 'must') === 'must');
                            $nice = array_filter($requirements, fn($r) => ($r->type ?? 'must') === 'nice');
                            ?>
                            <?php if ($must): ?>
                            <h3>Anforderungen</h3>
                            <ul>
                            <?php foreach ($must as $r): ?>
                                <li><?php echo $esc($r->description ?? $r->requirement_text ?? ''); ?></li>
                            <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if ($nice): ?>
                            <h3>Von Vorteil</h3>
                            <ul>
                            <?php foreach ($nice as $r): ?>
                                <li><?php echo $esc($r->description ?? $r->requirement_text ?? ''); ?></li>
                            <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="jpg-aside">
                        <!-- Benefits -->
                        <?php if (!empty($benefits)): ?>
                        <div class="jpg-aside-box">
                            <h2>Das bieten wir</h2>
                            <ul>
                            <?php foreach ($benefits as $b): ?>
                                <li class="jpg-benefit jpg-benefit-<?php echo $esc($b->source ?? 'job'); ?>"><?php echo (!empty($b->icon) ? $b->icon . ' ' : ''); echo $esc($b->title ?? ''); ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Skills -->
                        <?php if (!empty($skills)): ?>
                        <div class="jpg-aside-box">
                            <h2>Gefragt</h2>
                            <div class="jpg-skill-tags">
                            <?php foreach ($skills as $s): ?>
                                <span class="jpg-skill-tag"><?php echo $esc($s->skill_name ?? ''); ?></span>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bewerben / Apply-Modal-Trigger -->
            <div class="jpg-apply-section" id="apply">
                <h2>Jetzt bewerben</h2>
                <?php if (!empty($profile->description)): ?>
                <div style="margin-bottom:1.25rem;"><?php echo $profile->description; /* Already sanitized HTML */ ?></div>
                <?php endif; ?>
                <button type="button" class="jpg-btn-apply" onclick="jpgOpenApplyModal()">
                    📩 Jetzt bewerben
                </button>
            </div>

        </div>
    </div>

    <div class="wl-footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo $company ? $esc($company) : $esc(SITE_NAME ?? ''); ?></p>
        <p>
            <a href="<?php echo $esc(SITE_URL); ?>/datenschutz" target="_blank">Datenschutz</a> &middot;
            <a href="<?php echo $esc(SITE_URL); ?>/impressum" target="_blank">Impressum</a>
        </p>
    </div>

</div><!-- /.wl-container -->

<!-- ====================================================
     Bewerbungs-Modal (Whitelabel)
     ==================================================== -->
<div id="jpgApplyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:12px;max-width:580px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="padding:1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:#1e293b;">📩 Bewerbung: <?php echo htmlspecialchars($profile->title, ENT_QUOTES); ?></h3>
            <button type="button" onclick="jpgCloseApplyModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#64748b;line-height:1;">&times;</button>
        </div>
        <div id="jpgApplySuccess" style="display:none;padding:1rem 1.5rem;background:#d1fae5;color:#065f46;border-bottom:1px solid #6ee7b7;">
            ✅ <strong>Bewerbung eingereicht!</strong> Wir melden uns so schnell wie möglich.
        </div>
        <div id="jpgApplyError" style="display:none;padding:1rem 1.5rem;background:#fef2f2;color:#991b1b;border-bottom:1px solid #fecaca;"></div>

        <form id="jpgApplyForm" style="padding:1.5rem;" novalidate>
            <input type="hidden" name="_jpg_csrf" value="<?php echo htmlspecialchars($applyCsrf ?? '', ENT_QUOTES); ?>">
            <input type="text" name="_hp_name" style="display:none;position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="applicant_name" required autocomplete="name"
                       style="width:100%;padding:.65rem .85rem;border:2px solid #e2e8f0;border-radius:6px;font-size:.95rem;box-sizing:border-box;"
                       placeholder="Max Mustermann">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">E-Mail <span style="color:#ef4444;">*</span></label>
                <input type="email" name="applicant_email" required autocomplete="email"
                       style="width:100%;padding:.65rem .85rem;border:2px solid #e2e8f0;border-radius:6px;font-size:.95rem;box-sizing:border-box;"
                       placeholder="max@beispiel.de">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">Anschreiben <span style="color:#ef4444;">*</span></label>
                <textarea name="cover_letter" rows="5" required
                          style="width:100%;padding:.65rem .85rem;border:2px solid #e2e8f0;border-radius:6px;font-size:.95rem;resize:vertical;box-sizing:border-box;"
                          placeholder="Warum möchtest du bei uns arbeiten?"></textarea>
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">Lebenslauf (PDF/Word, max. 5 MB)</label>
                <input type="file" name="cv_file" accept=".pdf,.doc,.docx" style="width:100%;font-size:.9rem;">
            </div>
            <div style="display:flex;gap:.75rem;justify-content:flex-end;flex-wrap:wrap;">
                <button type="button" onclick="jpgCloseApplyModal()"
                        style="padding:.65rem 1.25rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;cursor:pointer;color:#475569;">
                    Abbrechen
                </button>
                <button type="submit" id="jpgApplySubmit"
                        style="padding:.65rem 1.5rem;background:var(--jpg-primary,#3b82f6);color:#fff;border:none;border-radius:6px;font-size:.9rem;font-weight:600;cursor:pointer;">
                    📩 Bewerbung absenden
                </button>
            </div>
            <p style="margin-top:1rem;font-size:.78rem;color:#94a3b8;text-align:center;">
                Mit dem Absenden stimmst du unserer
                <a href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES); ?>/datenschutz" style="color:#64748b;">Datenschutzerklärung</a> zu.
            </p>
        </form>
    </div>
</div>

<script>
(function () {
    var modal   = document.getElementById('jpgApplyModal');
    var form    = document.getElementById('jpgApplyForm');
    var success = document.getElementById('jpgApplySuccess');
    var errBox  = document.getElementById('jpgApplyError');
    var submitBtn = document.getElementById('jpgApplySubmit');

    window.jpgOpenApplyModal = function () {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };
    window.jpgCloseApplyModal = function () {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        errBox.style.display = 'none';
    };
    modal.addEventListener('click', function (e) {
        if (e.target === modal) window.jpgCloseApplyModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') window.jpgCloseApplyModal();
    });
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errBox.style.display  = 'none';
        success.style.display = 'none';
        submitBtn.disabled    = true;
        submitBtn.textContent = '⏳ Sende…';
        try {
            var fd  = new FormData(form);
            var res = await fetch('<?php echo defined('SITE_URL') ? htmlspecialchars(SITE_URL, ENT_QUOTES) : ''; ?>/jobs/<?php echo htmlspecialchars($profile->slug, ENT_QUOTES); ?>/apply', {
                method: 'POST', body: fd,
            });
            var text = await res.text();
            var data;
            try { data = JSON.parse(text); }
            catch (_) { throw new Error('Ungültige Server-Antwort. Bitte versuche es erneut.'); }
            if (data.success) {
                form.style.display    = 'none';
                success.style.display = 'block';
            } else {
                errBox.textContent    = data.error || 'Unbekannter Fehler.';
                errBox.style.display  = 'block';
                submitBtn.disabled    = false;
                submitBtn.textContent = '📩 Bewerbung absenden';
            }
        } catch (err) {
            errBox.textContent    = 'Netzwerkfehler: ' + err.message;
            errBox.style.display  = 'block';
            submitBtn.disabled    = false;
            submitBtn.textContent = '📩 Bewerbung absenden';
        }
    });
})();
</script>

</body>
</html>
