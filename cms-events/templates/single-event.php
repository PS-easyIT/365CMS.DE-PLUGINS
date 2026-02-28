<?php
/**
 * Single Event Detail – Hero + Bridge + Speaker-Section
 * Layout analog zur Company-Detailseite.
 *
 * Verfügbare Variablen (via extract()):
 *   $event    – object
 *   $speakers – array
 *   $settings – array
 *
 * @package CMS_Events
 */

if (!isset($event)) {
    return;
}

$e  = $event;
$id = (int) $e->id;

$title       = htmlspecialchars($e->title ?? '');
$category    = htmlspecialchars($e->category ?? '');
$desc        = $e->description ?? '';
$city        = htmlspecialchars($e->city ?? '');
$address     = htmlspecialchars($e->address ?? '');
$zip         = htmlspecialchars($e->zip ?? '');
$country     = htmlspecialchars($e->country ?? '');
$ev_date     = $e->event_date ?? '';
$ev_time     = $e->event_time ?? '';
$end_date    = $e->end_date ?? '';
$end_time    = $e->end_time ?? '';
$is_online   = !empty($e->is_online);
$online_url  = htmlspecialchars($e->online_url ?? '');
$reg_url     = htmlspecialchars($e->registration_url ?? '');
$capacity    = (int)($e->capacity ?? 0);
$status      = $e->status ?? 'published';
$is_featured = !empty($e->is_featured);
$banner_url  = htmlspecialchars($e->banner_url ?? '');
$image_url   = htmlspecialchars($e->image_url ?? '');
$price_type  = $e->price_type ?? 'free';
$price       = (float)($e->price ?? 0);
$price_cur   = htmlspecialchars($e->price_currency ?? 'EUR');
$tags_raw    = !empty($e->tags) ? (json_decode($e->tags, true) ?? []) : [];
$org_name    = htmlspecialchars($e->organizer_name    ?? '');
$org_email   = htmlspecialchars($e->organizer_email   ?? '');
$org_phone   = htmlspecialchars($e->organizer_phone   ?? '');
$org_website = htmlspecialchars($e->organizer_website ?? '');

$show_price = !empty($settings['show_price'])     && $settings['show_price']     !== '0';
$show_tags  = !empty($settings['show_tags'])       && $settings['show_tags']      !== '0';
$show_org   = !empty($settings['show_organizer'])  && $settings['show_organizer'] !== '0';

$base_url    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$archive_url = $base_url . '/' . ($settings['archive_slug'] ?? 'events') . '/';

$ev_ts  = $ev_date  ? strtotime($ev_date)  : 0;
$end_ts = $end_date ? strtotime($end_date) : 0;

$speakers = $speakers ?? [];

// Hero-Gradient – helles Blau als Fallback
$hdr_from  = htmlspecialchars($settings['color_hdr_from']  ?? '#dbeafe');
$hdr_to    = htmlspecialchars($settings['color_hdr_to']    ?? '#bfdbfe');
$hdr_title = htmlspecialchars($settings['color_hdr_title'] ?? '#1e3a5f');
$primary   = htmlspecialchars($settings['color_primary']   ?? '#3b82f6');
$accent    = htmlspecialchars($settings['color_accent']    ?? '#1d4ed8');
?>
<style>
:root {
  --ev-primary:  <?= $primary ?>;
  --ev-accent:   <?= $accent ?>;
  --ev-radius:   12px;
  --ev-shadow:   0 1px 3px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.04);
  --ev-shadow-h: 0 4px 20px rgba(0,0,0,.10);
  --ev-border:   #bfdbfe;
  --ev-light:    #eff6ff;
}
</style>

<div class="ev-single-v2">

  <nav class="ev-breadcrumb">
    <a href="<?= $archive_url ?>">← Events</a>
    <span class="ev-breadcrumb__sep">/</span>
    <span class="ev-breadcrumb__cur"><?= mb_strimwidth($title, 0, 60, '…') ?></span>
  </nav>

  <!-- Hero Header 250px -->
  <header class="ev-hero-v2" style="background:linear-gradient(135deg,<?= $hdr_from ?> 0%,<?= $hdr_to ?> 100%);">
    <?php if ($banner_url): ?>
      <div class="ev-hero-v2__bg" style="background-image:url('<?= $banner_url ?>');" aria-hidden="true"></div>
    <?php endif; ?>
    <?php if ($image_url): ?>
      <div class="ev-hero-v2__thumb" aria-hidden="true">
        <img src="<?= $image_url ?>" alt="<?= $title ?>">
      </div>
    <?php endif; ?>
    <div class="ev-hero-v2__inner">
      <?php if ($ev_ts): ?>
        <div class="ev-hero-v2__datebadge">
          <span class="ev-hero-v2__date-day"><?= date('d', $ev_ts) ?></span>
          <span class="ev-hero-v2__date-mon"><?= date('M', $ev_ts) ?></span>
          <span class="ev-hero-v2__date-year"><?= date('Y', $ev_ts) ?></span>
        </div>
      <?php endif; ?>
      <div class="ev-hero-v2__meta">
        <h1 class="ev-hero-v2__title" style="color:<?= $hdr_title ?>;"><?= $title ?></h1>
      </div>
    </div>
  </header>

  <!-- Bridge Cards (überlappen Hero) -->
  <div class="ev-bridge-v2">

    <div class="ev-bridge-v2__about">
      <h2 class="ev-bridge-v2__title">📅 Über diesen Event</h2>
      <?php if (!empty($desc) && trim($desc) !== ''): ?>
        <div class="ev-bridge-v2__text ev-wysiwyg-content"><?= $desc ?></div>
      <?php else: ?>
        <p class="ev-bridge-v2__text" style="color:#94a3b8;font-style:italic;">Noch keine Beschreibung hinterlegt.</p>
      <?php endif; ?>
    </div>

    <div class="ev-bridge-v2__contact">
      <h2 class="ev-bridge-v2__title">&#128203; Details &amp; Anmeldung</h2>
      <div class="ev-bridge-v2__contact-body">

        <!-- Anmelde-Button -->
        <?php if ($reg_url && $status !== 'cancelled'): ?>
          <a href="<?= $reg_url ?>" target="_blank" rel="noopener" class="ev-bridge-v2__book-btn">🎟 Jetzt anmelden</a>
        <?php elseif ($is_online && $online_url): ?>
          <a href="<?= $online_url ?>" target="_blank" rel="noopener" class="ev-bridge-v2__book-btn">🔗 Online-Link aufrufen</a>
        <?php endif; ?>

        <!-- Veranstalter Kontakt-Icons -->
        <?php if ($org_email || $org_phone || $org_website): ?>
        <div class="ev-bridge-v2__icon-row">
          <?php if ($org_website): ?>
            <a href="<?= $org_website ?>" target="_blank" rel="noopener" class="ev-bridge-v2__icon-btn">🌐 Web</a>
          <?php endif; ?>
          <?php if ($org_email): ?>
            <a href="mailto:<?= $org_email ?>" class="ev-bridge-v2__icon-btn">✉️ Mail</a>
          <?php endif; ?>
          <?php if ($org_phone): ?>
            <a href="tel:<?= $org_phone ?>" class="ev-bridge-v2__icon-btn">📞 Anruf</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <hr class="ev-bridge-v2__divider">

        <!-- Eventdaten -->
        <?php
        $facts = [];
        if ($ev_ts) {
            $datum = date('d.m.Y', $ev_ts);
            if ($ev_time) $datum .= ' · ' . htmlspecialchars($ev_time) . ' Uhr';
            $facts[] = ['📅 Datum', $datum];
        }
        if ($end_ts && $end_ts !== $ev_ts) {
            $end_str = date('d.m.Y', $end_ts);
            if ($end_time) $end_str .= ' · ' . htmlspecialchars($end_time) . ' Uhr';
            $facts[] = ['🏁 Ende', $end_str];
        }
        if ($category) $facts[] = ['📂 Kategorie', $category];
        if ($is_online) {
            $facts[] = ['🌐 Format', 'Online-Event'];
        } elseif ($city || $address) {
            $loc = trim(($address ? $address . ', ' : '') . ($zip ? $zip . ' ' : '') . $city);
            if ($country && $country !== 'Deutschland') $loc .= ', ' . $country;
            $facts[] = ['📍 Ort', htmlspecialchars($loc)];
        }
        if ($capacity) $facts[] = ['🪑 Kapazität', $capacity . ' Plätze'];
        if ($org_name)  $facts[] = ['🏢 Veranstalter', $org_name];
        if ($show_price) {
            if ($price_type === 'free')         $facts[] = ['💶 Preis', 'Kostenlos'];
            elseif ($price_type === 'donation') $facts[] = ['💶 Preis', 'Spendenbasis'];
            elseif ($price > 0)                 $facts[] = ['💶 Preis', number_format($price, 2, ',', '.') . ' ' . $price_cur];
        }
        ?>
        <?php if (!empty($facts)): ?>
        <div class="ev-bridge-v2__facts">
          <?php foreach ($facts as [$lbl, $val]): ?>
            <div class="ev-bridge-v2__fact">
              <span class="ev-bridge-v2__fact-lbl"><?= $lbl ?></span>
              <span class="ev-bridge-v2__fact-val"><?= $val ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tags -->
        <?php if ($show_tags && !empty($tags_raw)): ?>
          <div class="ev-bridge-v2__tags">
            <?php foreach ($tags_raw as $tag): ?>
              <span class="ev-bridge-v2__tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- Speaker – volle Breite, 3 Spalten -->
  <?php if (!empty($speakers)): ?>
  <div class="ev-people-v2">
    <div class="ev-sec-v2">
      <h2 class="ev-sec-v2__title">
        🎤 Speaker &amp; Referenten
        <span class="ev-section-count"><?= count($speakers) ?></span>
      </h2>
      <div class="ev-speaker-grid-v2">
        <?php foreach ((array)$speakers as $sp):
          $spFirst = $sp->first_name ?? '';
          $spLast  = $sp->last_name  ?? '';
          $spName  = htmlspecialchars(trim($spFirst . ' ' . $spLast) ?: 'Speaker');
          $spCity  = !empty($sp->location_city) ? htmlspecialchars($sp->location_city) : null;
          $spPhoto = !empty($sp->photo_url)      ? htmlspecialchars($sp->photo_url)     : null;
          $spId    = (int)($sp->id ?? 0);
          $letter = mb_strtoupper(mb_substr($spFirst ?: $spLast, 0, 1) ?: 'S');
          $colors = [['#3b82f6','#1d4ed8'],['#0891b2','#0284c7'],['#6366f1','#4f46e5'],['#8b5cf6','#7c3aed'],['#0ea5e9','#0369a1']];
          $sc     = $colors[abs(crc32($spFirst . $spLast)) % count($colors)];
          $spGrad = "linear-gradient(135deg,{$sc[0]},{$sc[1]})";
        ?>
          <div class="ev-spk-row">
            <div class="ev-spk-row__top">
              <?php if ($spPhoto): ?>
                <div class="ev-spk-row__av"><img src="<?= $spPhoto ?>" alt="<?= $spName ?>"></div>
              <?php else: ?>
                <div class="ev-spk-row__av" style="background:<?= $spGrad ?>"><?= $letter ?></div>
              <?php endif; ?>
              <div class="ev-spk-row__info">
                <div class="ev-spk-row__name">
                  <?php if ($spId > 0): ?>
                    <a href="<?= $base_url ?>/speakers/<?= $spId ?>"><?= $spName ?></a>
                  <?php else: ?>
                    <?= $spName ?>
                  <?php endif; ?>
                </div>
                <?php if ($spCity):  ?><div class="ev-spk-row__sub">📍 <?= $spCity ?></div><?php endif; ?>
              </div>
            </div>
            <?php if ($spId > 0): ?>
              <a href="<?= $base_url ?>/speakers/<?= $spId ?>" class="ev-spk-row__btn">Profil →</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!(int)($e->user_id ?? 0)): ?>
  <div class="ev-claim-banner">
    <div class="ev-claim-banner__text">
      <strong>Dieser Event wurde von der Redaktion eingetragen.</strong>
      Sind Sie der Veranstalter? Registrieren Sie sich kostenlos und verwalten Sie Ihren Event selbst.
    </div>
    <a href="<?= rtrim(SITE_URL, '/') ?>/register" class="ev-claim-banner__btn">Jetzt registrieren &amp; Event übernehmen →</a>
  </div>
  <?php endif; ?>

</div><!-- /.ev-single-v2 -->