<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Theme-integrierte Job-Einzelansicht
 *
 * @var object        $profile
 * @var array<object> $tasks
 * @var array<object> $requirements
 * @var array         $benefits
 * @var array<object> $skills
 * @var string        $company
 * @var string        $jsonld
 * @var string        $applyCsrf   CSRF-Token für Bewerbungsformular
 *
 * @since   0.0.1
 * @package CMS_JobProfileGenerator
 */

$esc = function (string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
};
?>
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

        <!-- Phase 6.3: Lerne dein Team kennen (cms-experts) -->
        <?php if (!empty($experts)): ?>
        <div class="jpg-section">
            <h2>👥 Lerne dein Team kennen</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-top:1rem;">
                <?php foreach ($experts as $expert): ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1.25rem;text-align:center;">
                    <?php if (!empty($expert->avatar_url)): ?>
                    <img src="<?php echo $esc($expert->avatar_url); ?>"
                         alt="<?php echo $esc($expert->name); ?>"
                         style="width:72px;height:72px;border-radius:50%;object-fit:cover;margin-bottom:.75rem;">
                    <?php else: ?>
                    <div style="width:72px;height:72px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-size:1.75rem;">👤</div>
                    <?php endif; ?>
                    <div style="font-weight:700;font-size:.95rem;color:#1e293b;"><?php echo $esc($expert->name); ?></div>
                    <?php if (!empty($expert->job_title)): ?>
                    <div style="font-size:.82rem;color:#64748b;margin-top:.2rem;"><?php echo $esc($expert->job_title); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($expert->bio_short)): ?>
                    <p style="font-size:.82rem;color:#475569;margin:.6rem 0 0;line-height:1.5;"><?php echo $esc($expert->bio_short); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bewerben / Apply-Modal -->
        <div class="jpg-apply-section" id="apply">
            <h2>Jetzt bewerben</h2>
            <?php if (!empty($profile->description)): ?>
            <div style="margin-bottom:1.25rem;"><?php echo $profile->description; /* Already sanitized HTML */ ?></div>
            <?php endif; ?>
            <button type="button" class="jpg-btn-apply" onclick="jpgOpenApplyModal()">
                📩 Jetzt bewerben
            </button>
        </div>

    </div><!-- /.jpg-job-body -->

    <!-- Google for Jobs JSON-LD -->
    <?php if (!empty($jsonld)): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>

</div><!-- /.jpg-public -->

<!-- ====================================================
     Bewerbungs-Modal
     ==================================================== -->
<div id="jpgApplyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:12px;max-width:580px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="padding:1.5rem;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:#1e293b;">📩 Bewerbung: <?php echo htmlspecialchars($profile->title, ENT_QUOTES); ?></h3>
            <button type="button" onclick="jpgCloseApplyModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#64748b;line-height:1;">&times;</button>
        </div>

        <!-- Erfolgs-Banner -->
        <div id="jpgApplySuccess" style="display:none;padding:1rem 1.5rem;background:#d1fae5;color:#065f46;border-bottom:1px solid #6ee7b7;">
            ✅ <strong>Bewerbung eingereicht!</strong> Wir melden uns so schnell wie möglich.
        </div>
        <!-- Fehler-Banner -->
        <div id="jpgApplyError" style="display:none;padding:1rem 1.5rem;background:#fef2f2;color:#991b1b;border-bottom:1px solid #fecaca;"></div>

        <form id="jpgApplyForm" style="padding:1.5rem;" novalidate>
            <input type="hidden" name="_jpg_csrf" value="<?php echo htmlspecialchars($applyCsrf ?? '', ENT_QUOTES); ?>">
            <!-- Honeypot -->
            <input type="text" name="_hp_name" style="display:none;position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">
                    Vollständiger Name <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="applicant_name" required autocomplete="name"
                       style="width:100%;padding:.65rem .85rem;border:2px solid #e2e8f0;border-radius:6px;font-size:.95rem;box-sizing:border-box;"
                       placeholder="Max Mustermann">
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">
                    E-Mail-Adresse <span style="color:#ef4444;">*</span>
                </label>
                <input type="email" name="applicant_email" required autocomplete="email"
                       style="width:100%;padding:.65rem .85rem;border:2px solid #e2e8f0;border-radius:6px;font-size:.95rem;box-sizing:border-box;"
                       placeholder="max@beispiel.de">
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">
                    Telefon (optional)
                </label>
                <input type="tel" name="applicant_phone" autocomplete="tel"
                       style="width:100%;padding:.65rem .85rem;border:2px solid #e2e8f0;border-radius:6px;font-size:.95rem;box-sizing:border-box;"
                       placeholder="+49 123 456789">
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">
                    Anschreiben <span style="color:#ef4444;">*</span>
                </label>
                <textarea name="cover_letter" rows="5" required
                          style="width:100%;padding:.65rem .85rem;border:2px solid #e2e8f0;border-radius:6px;font-size:.95rem;resize:vertical;box-sizing:border-box;"
                          placeholder="Warum möchtest du bei uns arbeiten? Was bringst du mit?"></textarea>
            </div>

            <div style="margin-bottom:1.5rem;">
                <label style="display:block;font-weight:600;font-size:.9rem;color:#1e293b;margin-bottom:.35rem;">
                    Lebenslauf / CV (PDF oder Word, max. 5 MB)
                </label>
                <input type="file" name="cv_file" accept=".pdf,.doc,.docx"
                       style="width:100%;padding:.5rem 0;font-size:.9rem;">
                <small style="color:#64748b;font-size:.8rem;">Optionale Datei – max. 5 MB, PDF oder Word</small>
            </div>

            <div style="display:flex;gap:.75rem;justify-content:flex-end;flex-wrap:wrap;">
                <button type="button" onclick="jpgCloseApplyModal()"
                        style="padding:.65rem 1.25rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;font-size:.9rem;cursor:pointer;color:#475569;">
                    Abbrechen
                </button>
                <button type="submit" id="jpgApplySubmit"
                        style="padding:.65rem 1.5rem;background:#3b82f6;color:#fff;border:none;border-radius:6px;font-size:.9rem;font-weight:600;cursor:pointer;">
                    📩 Bewerbung absenden
                </button>
            </div>

            <p style="margin-top:1rem;font-size:.78rem;color:#94a3b8;text-align:center;">
                Mit dem Absenden stimmst du der Verarbeitung deiner Daten gemäß unserer
                <a href="/datenschutz" style="color:#64748b;">Datenschutzerklärung</a> zu.
                Deine Daten werden ausschließlich zur Bearbeitung deiner Bewerbung verwendet.
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

    // Schließen per Klick auf Overlay
    modal.addEventListener('click', function (e) {
        if (e.target === modal) window.jpgCloseApplyModal();
    });

    // Schließen per Escape-Taste
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
                method: 'POST',
                body: fd,
            });
            var text = await res.text();
            var data;
            try { data = JSON.parse(text); }
            catch (_) { throw new Error('Ungültige Server-Antwort. Bitte versuche es erneut.'); }
            if (data.success) {
                form.style.display    = 'none';
                success.style.display = 'block';
            } else {
                errBox.textContent    = data.error || 'Unbekannter Fehler. Bitte versuche es erneut.';
                errBox.style.display  = 'block';
                submitBtn.disabled    = false;
                submitBtn.textContent = '📩 Bewerbung absenden';
            }
        } catch (err) {
            errBox.textContent   = 'Netzwerkfehler: ' + err.message;
            errBox.style.display = 'block';
            submitBtn.disabled   = false;
            submitBtn.textContent = '📩 Bewerbung absenden';
        }
    });
})();
</script>
