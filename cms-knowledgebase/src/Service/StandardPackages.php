<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Service;

use CmsKnowledgebase\Repository\EntryRepository;
use CmsKnowledgebase\Support\LoggerFactory;

if (!defined('ABSPATH')) {
    exit;
}

final class StandardPackages
{
    private static ?self $instance = null;

    private EntryRepository $repository;

    private $logger;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->repository = EntryRepository::instance();
        $this->logger = LoggerFactory::create();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPackages(): array
    {
        $packages = [];

        foreach (self::definitions() as $key => $package) {
            $entryTitles = array_map(
                static function (array|string $entry): string {
                    return is_array($entry) ? (string) ($entry['title'] ?? '') : (string) $entry;
                },
                $package['entries']
            );

            $packages[] = [
                'key' => $key,
                'label' => (string) $package['label'],
                'description' => (string) $package['description'],
                'accent' => (string) $package['accent'],
                'entry_count' => count($package['entries']),
                'sample_terms' => array_values(array_filter(array_slice($entryTitles, 0, 4))),
            ];
        }

        return $packages;
    }

    public function importPackage(string $packageKey): array
    {
        $definitions = self::definitions();
        if (!isset($definitions[$packageKey])) {
            return ['success' => false, 'error' => 'Unbekanntes Standardpaket.'];
        }

        $package = $definitions[$packageKey];
        $result = $this->repository->importPresetEntries($this->buildEntries($package), $packageKey);

        $message = sprintf(
            'Standardpaket „%s“ verarbeitet: %d neu angelegt, %d aktualisiert, %d übersprungen.',
            $package['label'],
            (int) ($result['created'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['skipped'] ?? 0)
        );

        if ((int) ($result['errors'] ?? 0) > 0) {
            $message .= sprintf(' %d Einträge konnten nicht erstellt werden.', (int) $result['errors']);
        }

        $this->logger->info('Knowledgebase-Standardpaket importiert.', [
            'package' => $packageKey,
            'created' => (int) ($result['created'] ?? 0),
            'updated' => (int) ($result['updated'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
            'errors' => (int) ($result['errors'] ?? 0),
        ]);

        return [
            'success' => ((int) ($result['created'] ?? 0) + (int) ($result['skipped'] ?? 0)) > 0,
            'message' => $message,
        ];
    }

    public function importAllPackages(): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $definitions = self::definitions();

        foreach (array_keys($definitions) as $packageKey) {
            $package = $definitions[$packageKey];
            $result = $this->repository->importPresetEntries($this->buildEntries($package), $packageKey);
            $created += (int) ($result['created'] ?? 0);
            $updated += (int) ($result['updated'] ?? 0);
            $skipped += (int) ($result['skipped'] ?? 0);
            $errors += (int) ($result['errors'] ?? 0);
        }

        return [
            'success' => ($created + $skipped) > 0,
            'message' => sprintf(
                'Alle Standardpakete verarbeitet: %d neu angelegt, %d aktualisiert, %d übersprungen%s.',
                $created,
                $updated,
                $skipped,
                $errors > 0 ? sprintf(', %d mit Fehlern', $errors) : ''
            ),
        ];
    }

    /**
     * @param array<string, mixed> $package
     * @return array<int, array<string, mixed>>
     */
    private function buildEntries(array $package): array
    {
        $entries = [];

        foreach ($package['entries'] as $index => $entry) {
            $normalized = $this->normalizeEntry($entry);
            $baseTitle = (string) $normalized['title'];
            $keyword = (string) $normalized['keyword'];
            $synonyms = $normalized['synonyms'];
            $definition = $this->definitionFor($baseTitle, (string) $package['label']);
            $summary = $this->buildExcerpt($baseTitle, $definition, (string) $package['label']);
            $content = $this->buildContent(
                $baseTitle,
                $this->articleTitleFor($baseTitle, (string) $package['label']),
                (string) $package['label'],
                (string) $package['description'],
                $definition,
                $keyword,
                $synonyms
            );

            $entries[] = [
                'title' => $this->articleTitleFor($baseTitle, (string) $package['label']),
                'keyword' => $keyword,
                'slug' => $baseTitle,
                'excerpt' => $summary,
                'tooltip_text' => $this->buildTooltip($baseTitle, $definition, (string) $package['label']),
                'synonyms' => implode("\n", $synonyms),
                'category' => (string) $package['label'],
                'priority' => 100 + ((int) $index * 10),
                'content' => $content,
                'is_active' => '1',
                'is_whole_word' => '1',
                'is_case_sensitive' => '0',
                'max_links_per_page' => '1',
            ];
        }

        return $entries;
    }

    /**
     * @param array<string, mixed>|string $entry
     * @return array{title: string, keyword: string, synonyms: array<int, string>}
     */
    private function normalizeEntry(array|string $entry): array
    {
        if (is_string($entry)) {
            return [
                'title' => $entry,
                'keyword' => $entry,
                'synonyms' => [],
            ];
        }

        $synonyms = array_values(array_filter(array_map(
            static fn(mixed $value): string => trim((string) $value),
            is_array($entry['synonyms'] ?? null) ? $entry['synonyms'] : []
        )));

        return [
            'title' => (string) ($entry['title'] ?? ''),
            'keyword' => (string) ($entry['keyword'] ?? ($entry['title'] ?? '')),
            'synonyms' => $synonyms,
        ];
    }

    private function buildExcerpt(string $title, string $definition, string $area): string
    {
        $excerpt = sprintf(
            '%s %s Der Beitrag bündelt allgemeine Infos, Lizenz- oder Verfügbarkeitsinfos und kurze Hinweise für %s.',
            $title,
            $definition,
            $this->contextLabelFor($area)
        );

        return mb_substr($excerpt, 0, 280, 'UTF-8');
    }

    private function buildTooltip(string $title, string $definition, string $area): string
    {
        $tooltip = sprintf('%s: %s Kompakte Infos zu Einordnung, Lizenzlage und Verfügbarkeit für %s.', $title, $definition, $this->contextLabelFor($area));
        return mb_substr($tooltip, 0, 220, 'UTF-8');
    }

    /**
     * @param array<int, string> $synonyms
     */
    private function buildContent(string $baseTitle, string $articleTitle, string $area, string $areaDescription, string $definition, string $keyword, array $synonyms): string
    {
        $licenseInfo = $this->buildList($this->licenseInfoFor($baseTitle, $area));
        $attentionPoints = $this->buildList($this->notesFor($baseTitle, $area, $areaDescription));
        $links = $this->buildLinksList($this->sourceLinksFor($baseTitle, $area));
        $summary = htmlspecialchars($this->summaryFor($baseTitle, $definition, $area), ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<h2>Allgemeine Infos</h2><p>%1$s</p><h2>Lizenz &amp; Verfügbarkeit</h2><ul>%2$s</ul><h2>Wichtige Hinweise</h2><ul>%3$s</ul><h2>Weiterführende Links</h2><ul>%4$s</ul>',
            $summary,
            $licenseInfo,
            $attentionPoints,
            $links
        );
    }

    /**
     * @param array<int, string> $items
     */
    private function buildList(array $items): string
    {
        $html = [];
        foreach ($items as $item) {
            $html[] = '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
        }

        return implode('', $html);
    }

    /**
     * @param array<int, array{label: string, url: string}> $items
     */
    private function buildLinksList(array $items): string
    {
        $html = [];
        foreach ($items as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }

            $html[] = sprintf(
                '<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            );
        }

        return implode('', $html);
    }

    private function articleTitleFor(string $title, string $area): string
    {
        $key = mb_strtolower($title, 'UTF-8');

        return match ($key) {
            'exchange online' => 'Exchange Online – Überblick, Postfachverwaltung und PowerShell-Praxis',
            'microsoft teams' => 'Microsoft Teams – Einführung, Rollout und Admin-Praxis',
            'microsoft entra id' => 'Microsoft Entra ID – Identitäten, Rollen und Zugriff',
            'azure app service' => 'Azure App Service – Webhosting, Deployment und Betrieb',
            'meta description' => 'Meta Description – Snippets, Inhalte und Best Practices',
            'title tag' => 'Title Tag – Seitentitel, Sichtbarkeit und Best Practices',
            'nis2' => 'NIS2 – Pflichten, Betroffenheit und Umsetzung',
            default => match ($area) {
                'M365' => $title . ' – Überblick, Einsatz und Admin-Praxis',
                'Azure & Cloud' => $title . ' – Überblick, Architektur und Betrieb',
                'Security & Compliance' => $title . ' – Bedeutung, Risiken und Maßnahmen',
                'Infrastruktur & Netzwerk' => $title . ' – Funktion, Betrieb und Stolperfallen',
                'Websites & SEO' => $title . ' – Bedeutung, Umsetzung und Best Practices',
                'Support & Automatisierung' => $title . ' – Standard, Ablauf und Praxis',
                default => $title,
            },
        };
    }

    private function summaryFor(string $title, string $definition, string $area): string
    {
        return sprintf(
            '%s %s Im Fokus stehen allgemeine Einordnung, Lizenz- und Verfügbarkeitsinfos sowie kurze operative Hinweise für %s.',
            $title,
            $definition,
            $this->contextLabelFor($area)
        );
    }

    /**
     * @return array<int, string>
     */
    private function licenseInfoFor(string $title, string $area): array
    {
        $key = mb_strtolower($title, 'UTF-8');

        $generic = match ($area) {
            'M365' => [
                'Funktionen und Grenzen hängen in Microsoft 365 oft von Lizenzplan, Add-ons und Admin-Rollen ab.',
                'Vor produktiver Nutzung sollte geprüft werden, ob der jeweilige Tenant-Plan die gewünschte Funktion bereits enthält.',
            ],
            'Azure & Cloud' => [
                'In Azure spielen meist Tarif, Verbrauch, Region und aktivierte Zusatzdienste eine größere Rolle als klassische Einzellizenzen.',
                'Kosten und Verfügbarkeit sollten immer pro Subscription, Diensttyp und Betriebsmodell bewertet werden.',
            ],
            'Security & Compliance' => [
                'Einige Schutz- und Compliance-Funktionen setzen höhere Pläne, Add-ons oder organisatorische Nachweise voraus.',
                'Regulatorische Anforderungen ersetzen keine Produktlizenz; beides muss getrennt betrachtet werden.',
            ],
            'Infrastruktur & Netzwerk' => [
                'Bei Infrastrukturthemen hängen Verfügbarkeit und Funktionsumfang eher von Hardware, Herstellerlizenz oder Supportvertrag ab.',
                'Vor Beschaffung oder Rollout sollte geklärt sein, welche Editionen, Wartungsverträge oder Subscriptions nötig sind.',
            ],
            'Websites & SEO' => [
                'Viele SEO- und Website-Grundlagen benötigen keine gesonderte Lizenz, wohl aber passende CMS-, Hosting- oder Tool-Zugänge.',
                'Zusatztools für Analyse, Monitoring oder Optimierung sollten getrennt von der redaktionellen Arbeit betrachtet werden.',
            ],
            'Support & Automatisierung' => [
                'Service- und Automatisierungsfunktionen hängen häufig von eingesetzter Plattform, Edition und Integrationsumfang ab.',
                'Vor Automatisierung sollte geprüft werden, ob APIs, Workflows oder Rollen im vorhandenen Lizenzmodell enthalten sind.',
            ],
            default => [
                'Lizenz- und Verfügbarkeitsinfos sollten immer getrennt vom reinen Begriff erklärt werden.',
            ],
        };

        $specific = match ($key) {
            'exchange online' => [
                'Die Größe eines Exchange-Online-Postfachs wird laut Microsoft grundsätzlich durch die zugewiesene Abonnementlizenz bestimmt.',
                'Zusätzliche Quotas lassen sich administrativ anpassen, ändern aber nicht den eigentlichen Lizenzrahmen des Postfachs.',
            ],
            'microsoft teams' => [
                'Der nutzbare Teams-Funktionsumfang hängt vom Microsoft-365-Plan und optionalen Add-ons wie Telefonie oder Audiokonferenzen ab.',
            ],
            'microsoft entra id' => [
                'Erweiterte Entra-Funktionen wie bestimmte Schutz- oder Governance-Features können vom gebuchten Entra-Plan abhängen.',
            ],
            'azure app service' => [
                'App Service wird nicht klassisch pro Benutzer lizenziert, sondern nach Plan, Größe, Laufzeit und genutzten Ressourcen betrieben.',
            ],
            'meta description', 'title tag', 'technical seo' => [
                'Für Title Tag und Meta Description ist in der Regel keine Zusatzlizenz nötig; entscheidend sind CMS-Zugriff und redaktionelle Prozesse.',
            ],
            'nis2' => [
                'NIS2 ist keine Produktlizenz, sondern ein regulatorischer Rahmen mit Pflichten für betroffene Einrichtungen.',
            ],
            'lizenzmanagement' => [
                'Der Begriff betrifft selbst die Verwaltung von Lizenzmodellen, Zuweisungen, Laufzeiten und Kostenkontrolle.',
            ],
            default => [],
        };

        return array_values(array_unique(array_merge($specific, $generic)));
    }

    private function contextLabelFor(string $area): string
    {
        return match ($area) {
            'M365' => 'den Microsoft-365-Alltag',
            'Azure & Cloud' => 'Cloud-Architektur und Plattformbetrieb',
            'Security & Compliance' => 'Sicherheits- und Compliance-Themen',
            'Infrastruktur & Netzwerk' => 'Infrastruktur- und Netzwerkbetrieb',
            'Websites & SEO' => 'Webprojekte, Sichtbarkeit und Content',
            'Support & Automatisierung' => 'Support, Übergaben und Automatisierung',
            default => 'den operativen Alltag',
        };
    }

    private function articleTypeFor(string $area): string
    {
        return match ($area) {
            'M365' => 'Admin- und Betriebsartikel',
            'Azure & Cloud' => 'Architektur- und Betriebsartikel',
            'Security & Compliance' => 'Governance- und Schutzartikel',
            'Infrastruktur & Netzwerk' => 'Betriebs- und Grundlagenartikel',
            'Websites & SEO' => 'Content- und Optimierungsartikel',
            'Support & Automatisierung' => 'Prozess- und Serviceartikel',
            default => 'Wissensartikel',
        };
    }

    private function readerRoleFor(string $area): string
            {
                return match ($area) {
                    'M365' => 'Administratoren, Support und Projektverantwortliche',
                    'Azure & Cloud' => 'Cloud-Admins, Architekturteams und Betrieb',
                    'Security & Compliance' => 'IT-Leitung, Security-Verantwortliche und Audits',
                    'Infrastruktur & Netzwerk' => 'IT-Betrieb, Infrastrukturteams und Support',
                    'Websites & SEO' => 'Redaktion, Webentwicklung und Marketing',
                    'Support & Automatisierung' => 'Service Desk, Operations und Prozessowner',
                    default => 'IT- und Fachverantwortliche',
                };
            }

            private function previewSlug(string $value): string
            {
                $value = mb_strtolower(trim($value), 'UTF-8');
                $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';
                return trim(substr($value, 0, 190), '-');
            }

            /**
             * @return array<int, string>
             */
            private function prerequisitesFor(string $title, string $area, string $areaDescription): array
            {
                $key = mb_strtolower($title, 'UTF-8');

                $generic = match ($area) {
                    'M365' => [
                        'Tenant-Kontext, Rollen und Lizenzsituation sollten vor der Bewertung bekannt sein.',
                        'Der betroffene Dienst und seine Abhängigkeiten zu Identität, Sicherheit und Endgeräten müssen sauber eingeordnet sein.',
                        'Administrative Zuständigkeit zwischen Fachbereich, Support und M365-Administration sollte geklärt sein.',
                    ],
                    'Azure & Cloud' => [
                        'Subscription, Resource Group und Verantwortlichkeiten sollten feststehen.',
                        'Betriebsmodell für Monitoring, Backup, Kosten und Security muss mitgedacht werden.',
                        'Der Dienst sollte im Kontext von Netzwerk, Zugriff und Deployment-Pfad betrachtet werden.',
                    ],
                    'Security & Compliance' => [
                        'Schutzziele, Risiken und betroffene Prozesse sollten bekannt sein.',
                        'Zuständigkeiten für Kontrolle, Nachweis und Eskalation müssen vorab geklärt sein.',
                        'Der Begriff sollte nicht isoliert, sondern im Rahmen von Richtlinien und Audits eingeordnet werden.',
                    ],
                    'Infrastruktur & Netzwerk' => [
                        'Topologie, Standardkonfiguration und betroffene Systeme sollten dokumentiert sein.',
                        'Auswirkung auf Verfügbarkeit, Monitoring und Support muss nachvollziehbar sein.',
                        'Änderungs- und Rückfallpfade sollten vor produktiven Eingriffen definiert sein.',
                    ],
                    'Websites & SEO' => [
                        'Zielseite, Zielgruppe und Suchintention sollten klar benannt sein.',
                        'Redaktion, Technik und Messbarkeit müssen auf denselben Seitenkontext schauen.',
                        'Verantwortung für Pflege, Review und Aktualität sollte festgelegt sein.',
                    ],
                    'Support & Automatisierung' => [
                        'Auslöser, gewünschtes Ergebnis und beteiligte Rollen müssen bekannt sein.',
                        'Der Prozess braucht eine dokumentierte Ausnahme- oder Eskalationslogik.',
                        'Automatisierung sollte nur auf belastbaren und wiederholbaren Schritten aufbauen.',
                    ],
                    default => [sprintf('%s sollte im fachlichen und technischen Kontext eindeutig beschrieben werden.', $title)],
                };

                $specific = match ($key) {
                    'exchange online' => [
                        'Für tiefere Verwaltungsaufgaben wird laut Microsoft eine Verbindung zu Exchange Online PowerShell benötigt.',
                        'Ein Microsoft-365-Administratorkonto beziehungsweise passende Exchange-Rollen sollten vorhanden sein.',
                        'Lizenz- und Speichergrenzen des Postfachs müssen vor Änderungen geprüft werden.',
                    ],
                    'microsoft teams' => [
                        'Für ein sauberes Rollout sollten Pilotgruppe, Governance und Kommunikationskonzept vorbereitet sein.',
                        'Netzwerk-, Meeting- und Telefonie-Anforderungen sollten je nach Einsatzszenario geprüft werden.',
                    ],
                    'microsoft entra id' => [
                        'Benutzer-, Gruppen- und Rollenmodell des Tenants sollte verstanden sein.',
                        'Abhängigkeiten zu Apps, Conditional Access und Identitätsschutz gehören früh auf den Tisch.',
                    ],
                    'azure app service' => [
                        'Der Ziel-Stack und das gewünschte Deployment-Verfahren sollten vorab feststehen.',
                        'Logging, Diagnose, Domänen und Zertifikate gehören zur Betriebsplanung von Beginn an dazu.',
                    ],
                    'meta description' => [
                        'Jede wichtige URL braucht eine eigene, inhaltlich passende Kurzbeschreibung.',
                        'Die Beschreibung sollte den Seiteninhalt zusammenfassen statt nur Keywords aufzuzählen.',
                    ],
                    'title tag' => [
                        'Jede Seite sollte einen eindeutigen und prägnanten Seitentitel besitzen.',
                        'Der sichtbare Haupttitel und der Title Tag sollten inhaltlich zueinander passen.',
                    ],
                    'nis2' => [
                        'Betroffenheitsprüfung, Risikobewertung und Management-Verantwortung müssen sauber vorbereitet sein.',
                        'Regelmäßige, risikoorientierte Schulungen gehören laut BSI früh in die Planung.',
                    ],
                    default => [],
                };

                return array_values(array_unique(array_merge($specific, $generic, [sprintf('Der Eintrag gehört zum Themenbereich %s.', $areaDescription)])));
            }

            /**
             * @return array<int, string>
             */
            private function notesFor(string $title, string $area, string $areaDescription): array
            {
                $key = mb_strtolower($title, 'UTF-8');
                $notes = $this->attentionPointsFor($title, $area);
                $notes[] = $this->practiceNoteFor($title, $area, $areaDescription);

                $specific = match ($key) {
                    'exchange online' => [
                        'Microsoft trennt zwischen lizenzbedingter Postfachgröße und individuell gesetzten Quotas; beides sollte nicht verwechselt werden.',
                        'Quota-Änderungen sollten nach der Anpassung mit Get-Mailbox kontrolliert und dokumentiert werden.',
                    ],
                    'microsoft teams' => [
                        'Microsoft empfiehlt für größere Umgebungen ein stufenweises Rollout statt eines Big-Bang-Ansatzes.',
                        'Teams ist eng mit Microsoft 365 Groups, SharePoint, Exchange Online und OneNote verzahnt.',
                    ],
                    'microsoft entra id' => [
                        'Ein Tenant ist in Microsoft 365 und Azure automatisch auch ein Entra-Tenant; Identitätsthemen sind daher selten isoliert.',
                        'Rollen und Policies sollten immer mit Zero-Trust- und Least-Privilege-Sicht beschrieben werden.',
                    ],
                    'azure app service' => [
                        'App Service wirkt zwar einfach, braucht aber trotzdem klare Entscheidungen zu Sicherheit, CI/CD, Domains und Observability.',
                        'Bei produktiven Web-Apps sollten Skalierung, SSL und Diagnosefunktionen nicht erst nach dem Go-live ergänzt werden.',
                    ],
                    'meta description' => [
                        'Google erzeugt Snippets häufig aus dem Seiteninhalt; die Meta Description ist also kein Freifahrtschein für irrelevante Werbetexte.',
                        'Aussagekräftige, konkrete Beschreibungen helfen eher als generische oder mehrfach wiederverwendete Texte.',
                    ],
                    'title tag' => [
                        'Zu generische oder wiederholte Titel können von Google umgeschrieben werden, wenn sie den Seiteninhalt nicht sauber abbilden.',
                        'Sprache, Hauptüberschrift und Titelmuster sollten konsistent sein, damit Suchergebnis und Seite zusammenpassen.',
                    ],
                    'nis2' => [
                        'Die Umsetzung ist nicht nur Technikarbeit: Geschäftsleitung, Schulung und Nachweisführung spielen ausdrücklich mit hinein.',
                        'Der Reifegrad steigt deutlich, wenn Pflichtenthemen früh als Programm statt als Einzelmaßnahme geplant werden.',
                    ],
                    default => [],
                };

                return array_values(array_unique(array_merge($specific, $notes)));
            }

            /**
             * @return array<int, array{label: string, url: string}>
             */
            private function sourceLinksFor(string $title, string $area): array
            {
                $key = mb_strtolower($title, 'UTF-8');

                $specific = match ($key) {
                    'exchange online' => [
                        ['label' => 'Microsoft Learn: Exchange Online Überblick', 'url' => 'https://learn.microsoft.com/de-de/exchange/exchange-online'],
                        ['label' => 'Microsoft Learn: Exchange Online mit PowerShell verwalten', 'url' => 'https://learn.microsoft.com/de-de/training/modules/manage-exchange-online-use-windows-powershell/'],
                        ['label' => 'Microsoft Learn: Postfachgröße in Exchange Online anpassen', 'url' => 'https://learn.microsoft.com/de-de/exchange/troubleshoot/user-and-shared-mailboxes/increase-or-customize-mailbox-size'],
                    ],
                    'microsoft teams' => [
                        ['label' => 'Microsoft Learn: Microsoft Teams Überblick', 'url' => 'https://learn.microsoft.com/de-de/microsoftteams/teams-overview'],
                        ['label' => 'Microsoft Learn: Teams-Bereitstellung für IT-Admins', 'url' => 'https://learn.microsoft.com/de-de/microsoftteams/deploy-overview'],
                    ],
                    'microsoft entra id' => [
                        ['label' => 'Microsoft Learn: Was ist Microsoft Entra?', 'url' => 'https://learn.microsoft.com/de-de/entra/fundamentals/whatis'],
                    ],
                    'azure app service' => [
                        ['label' => 'Microsoft Learn: Azure App Service Überblick', 'url' => 'https://learn.microsoft.com/de-de/azure/app-service/overview'],
                        ['label' => 'Microsoft Learn: Erste Schritte mit Azure App Service', 'url' => 'https://learn.microsoft.com/de-de/azure/app-service/getting-started'],
                    ],
                    'technical seo' => [
                        ['label' => 'Google Search Central: SEO-Starter-Guide', 'url' => 'https://developers.google.com/search/docs/fundamentals/seo-starter-guide?hl=de'],
                        ['label' => 'Google Search Central: Titellinks', 'url' => 'https://developers.google.com/search/docs/appearance/title-link?hl=de'],
                        ['label' => 'Google Search Central: Snippets', 'url' => 'https://developers.google.com/search/docs/appearance/snippet?hl=de'],
                    ],
                    'meta description' => [
                        ['label' => 'Google Search Central: Snippets und Meta-Beschreibungen', 'url' => 'https://developers.google.com/search/docs/appearance/snippet?hl=de'],
                        ['label' => 'Google Search Central: SEO-Starter-Guide', 'url' => 'https://developers.google.com/search/docs/fundamentals/seo-starter-guide?hl=de'],
                    ],
                    'title tag' => [
                        ['label' => 'Google Search Central: Titellinks', 'url' => 'https://developers.google.com/search/docs/appearance/title-link?hl=de'],
                        ['label' => 'Google Search Central: SEO-Starter-Guide', 'url' => 'https://developers.google.com/search/docs/fundamentals/seo-starter-guide?hl=de'],
                    ],
                    'nis2' => [
                        ['label' => 'BSI: NIS-2-Richtlinie', 'url' => 'https://www.bsi.bund.de/dok/nis-2-richtlinie'],
                        ['label' => 'BSI: Allgemeine FAQ zu NIS-2', 'url' => 'https://www.bsi.bund.de/dok/nis-2-faq-allgemein'],
                    ],
                    default => [],
                };

                $generic = match ($area) {
                    'M365', 'Azure & Cloud', 'Security & Compliance', 'Infrastruktur & Netzwerk', 'Support & Automatisierung' => [
                        ['label' => 'Microsoft Learn: Suche zu diesem Thema', 'url' => 'https://learn.microsoft.com/de-de/search/?terms=' . rawurlencode($title)],
                    ],
                    'Websites & SEO' => [
                        ['label' => 'Google Search Central: SEO-Starter-Guide', 'url' => 'https://developers.google.com/search/docs/fundamentals/seo-starter-guide?hl=de'],
                        ['label' => 'Google Search Central: Dokumentationsübersicht', 'url' => 'https://developers.google.com/search/docs?hl=de'],
                    ],
                    default => [],
                };

                $links = [];
                $seen = [];
                foreach (array_merge($specific, $generic) as $item) {
                    $url = (string) ($item['url'] ?? '');
                    if ($url === '' || isset($seen[$url])) {
                        continue;
                    }

                    $seen[$url] = true;
                    $links[] = $item;
                }

                return array_slice($links, 0, 4);
            }
    private function exampleHtmlFor(string $title, string $area): string
    {
        $key = mb_strtolower($title, 'UTF-8');

        return match ($key) {
            'exchange online' => '<h2>Praxisbeispiel</h2><p>Wenn eine Lizenz zwar genug Speicher zulässt, ein Postfach aber bewusst kleiner begrenzt werden soll, beschreibt Microsoft die Anpassung über Exchange Online PowerShell. Typisch ist eine getrennte Steuerung für Warnung, Senden und Senden/Empfangen.</p><pre><code>Set-Mailbox user@contoso.com -IssueWarningQuota 18GB -ProhibitSendQuota 19GB -ProhibitSendReceiveQuota 20GB
Get-Mailbox user@contoso.com | Select *quota*</code></pre><p>Damit passt der Eintrag bewusst zu einem operativen KB-Artikel: kurz erklären, Voraussetzungen nennen, Änderung dokumentieren und das Ergebnis direkt verifizieren.</p>',
            'microsoft teams' => '<h2>Praxisbeispiel</h2><p>Für mittlere und größere Organisationen empfiehlt Microsoft einen Pilotrollout. Typisch ist eine schrittweise Einführung nach Workloads wie Chat &amp; Teams, Besprechungen, Audiokonferenzen und Cloud Voice, statt sofort alles gleichzeitig auszurollen.</p>',
            'microsoft entra id' => '<h2>Praxisbeispiel</h2><p>Ein sauberer Entra-ID-Eintrag sollte nicht nur Benutzer und Gruppen nennen, sondern auch Rollen, Anwendungen, Conditional Access und den Tenant-Kontext. Gerade dadurch wird aus einem Identitätsbegriff ein belastbarer Betriebsartikel.</p>',
            'azure app service' => '<h2>Praxisbeispiel</h2><p>Für eine neue Web-App werden in der Praxis meist zuerst Stack, Deployment-Weg, Monitoring, benutzerdefinierte Domäne und Zertifikate festgezurrt. Genau diese Reihenfolge hilft dabei, App Service nicht nur als Hosting-Begriff, sondern als Betriebsbaustein zu dokumentieren.</p>',
            'meta description' => '<h2>Praxisbeispiel</h2><p>Google empfiehlt eindeutige, aussagekräftige Beschreibungen pro wichtiger URL. Statt reiner Keyword-Listen sollte die Beschreibung knapp zusammenfassen, was Nutzer auf der Seite wirklich erwartet.</p>',
            'title tag' => '<h2>Praxisbeispiel</h2><p>Ein guter Title Tag beschreibt die Seite prägnant, bleibt sprachlich konsistent zum Inhalt und deckt sich mit dem sichtbaren Hauptthema. Wenn Titel zu generisch oder mehrfach wiederholt werden, kann Google sie für Suchergebnisse anpassen.</p>',
            'nis2' => '<h2>Praxisbeispiel</h2><p>Das BSI betont bei NIS2 nicht nur Technik, sondern auch Betroffenheitsprüfung, Management-Verantwortung und angemessene Schulungsintervalle. Ein guter KB-Eintrag hält diese Governance-Sicht deshalb ausdrücklich fest.</p>',
            default => $area === 'Support & Automatisierung'
                ? '<h2>Praxisbeispiel</h2><p>Ein belastbarer Serviceartikel beschreibt Auslöser, beteiligte Rollen, gewünschtes Ergebnis und Eskalation in genau dieser Reihenfolge. Dadurch bleibt der Inhalt auch für Vertretungen und Onboarding nutzbar.</p>'
                : '',
        };
    }

    private function definitionFor(string $title, string $area): string
    {
        $key = mb_strtolower($title, 'UTF-8');

        return match ($key) {
            'microsoft 365 tenant' => 'ist die zentrale Instanz einer Microsoft-365-Umgebung, in der Benutzer, Lizenzen, Dienste und Sicherheitsvorgaben verwaltet werden.',
            'exchange online' => 'ist der cloudbasierte E-Mail- und Kalenderdienst von Microsoft 365 für Postfächer, Freigaben und Transportregeln.',
            'sharepoint online' => 'ist die Dokumenten- und Intranet-Plattform in Microsoft 365 für Teamseiten, Berechtigungen und strukturierte Inhalte.',
            'microsoft teams' => 'ist die Kollaborationsplattform für Chats, Besprechungen, Telefonie und Zusammenarbeit in Projekten und Fachbereichen.',
            'onedrive for business' => 'ist der persönliche Cloudspeicher für geschäftliche Dateien mit Freigaben, Versionsverlauf und Synchronisation.',
            'microsoft entra id' => 'ist der Identitätsdienst von Microsoft für Benutzerkonten, Gruppen, Anwendungen und Zugriffsrichtlinien.',
            'microsoft intune' => 'ist die Plattform für Geräteverwaltung, Compliance-Regeln und App-Verteilung auf Clients und Mobilgeräten.',
            'conditional access' => 'beschreibt richtlinienbasierten Zugriff, der Anmeldung und Ressourcenzugriff von Risiko, Gerät und Standort abhängig macht.',
            'multi-faktor-authentifizierung' => 'ergänzt das Passwort um einen weiteren Faktor und senkt damit das Risiko kompromittierter Konten deutlich.',
            'defender for office 365' => 'schützt E-Mail, Links und Anhänge in Microsoft 365 vor Phishing, Malware und Business-E-Mail-Compromise.',
            'teams telefonie' => 'integriert Telefonie in Microsoft Teams und ersetzt oder ergänzt klassische TK-Anlagen durch Cloud-Kommunikation.',
            'microsoft planner' => 'ist ein einfaches Aufgabenboard für Teamplanung, Zuständigkeiten und Fortschrittsverfolgung.',
            'microsoft to do' => 'ist eine persönliche Aufgabenverwaltung für Tagesplanung, Erinnerungen und kleine Arbeitslisten.',
            'power automate' => 'automatisiert wiederkehrende Prozesse zwischen Microsoft- und Drittsystemen über Trigger, Aktionen und Genehmigungen.',
            'power apps' => 'ermöglicht die Entwicklung fachbereichsnaher Anwendungen mit Formularen, Datenanbindung und Prozesslogik.',
            'microsoft forms' => 'ist ein Formular- und Umfragetool für Feedback, Anmeldungen, Quizze und einfache Datenerfassung.',
            'microsoft bookings' => 'organisiert Terminbuchungen mit Verfügbarkeiten, Bestätigungen und Zuordnung zu Mitarbeitenden oder Services.',
            'microsoft loop' => 'stellt flexible, gemeinsam bearbeitbare Inhaltsbausteine für Projekte, Meetings und Wissenssammlungen bereit.',
            'viva engage' => 'ist die Community- und Kommunikationsplattform für soziale Interaktion, Gruppen und interne Vernetzung.',
            'viva insights' => 'liefert Auswertungen und Impulse zu Arbeitsgewohnheiten, Fokuszeiten, Meetings und Zusammenarbeit.',
            'microsoft purview' => 'bündelt Compliance-, Datenschutz- und Informationsschutzfunktionen wie Klassifizierung, Aufbewahrung und Audit.',
            'aufbewahrungsrichtlinien' => 'regeln, wie lange Inhalte aufbewahrt, archiviert oder gelöscht werden müssen.',
            'sensitivity labels' => 'klassifizieren Inhalte nach Schutzbedarf und steuern Kennzeichnung, Verschlüsselung und Freigabeoptionen.',
            'copilot for microsoft 365' => 'nutzt KI im Microsoft-365-Kontext, um Inhalte zu entwerfen, Informationen zusammenzufassen und Aufgaben zu beschleunigen.',
            'hybrid identity' => 'verbindet lokale Verzeichnisdienste mit Cloud-Identitäten, damit Benutzer konsistent auf beide Welten zugreifen können.',
            'azure subscription' => 'ist die Abrechnungs- und Verwaltungsgrenze für Azure-Ressourcen, Berechtigungen und Budgets.',
            'resource group' => 'fasst zusammengehörige Azure-Ressourcen logisch für Verwaltung, Berechtigungen und Lebenszyklus zusammen.',
            'virtual network' => 'ist das isolierte Netzwerk in Azure, in dem Subnetze, Dienste und Sicherheitsregeln organisiert werden.',
            'network security group' => 'steuert per Regelwerk, welcher Netzwerkverkehr auf Subnetz- oder Netzwerkschnittstellenebene erlaubt ist.',
            'azure app service' => 'stellt Webanwendungen und APIs als verwalteten PaaS-Dienst ohne eigenen Serverbetrieb bereit.',
            'azure functions' => 'führt ereignisgesteuerten Code serverlos aus und skaliert abhängig von Triggern und Last.',
            'azure storage account' => 'ist der zentrale Container für Azure-Speicherdienste wie Blobs, Dateien, Warteschlangen und Tabellen.',
            'azure blob storage' => 'speichert unstrukturierte Daten wie Dateien, Backups, Medien oder Exporte hochskalierbar im Objektformat.',
            'azure files' => 'bietet SMB-basierte Dateifreigaben aus Azure, die lokal und in Cloud-Workloads eingebunden werden können.',
            'azure backup' => 'sichert Workloads und Systeme zentral mit Richtlinien, Aufbewahrung und Wiederherstellungsoptionen.',
            'azure site recovery' => 'repliziert Systeme für Desaster-Szenarien und unterstützt Failover in eine Azure-Zielumgebung.',
            'azure key vault' => 'verwaltet Geheimnisse, Zertifikate und Schlüssel zentral und abgesichert für Anwendungen und Administratoren.',
            'azure monitor' => 'sammelt Metriken, Logs und Signale zur Überwachung von Plattform, Anwendungen und Infrastruktur.',
            'log analytics' => 'ist der Analysebereich für Azure-Logs, in dem Daten gesammelt, korreliert und per Abfrage ausgewertet werden.',
            'azure policy' => 'erzwingt Governance-Regeln, überprüft Konfigurationen und verhindert unerwünschte Abweichungen.',
            'azure virtual desktop' => 'stellt zentral verwaltete virtuelle Windows-Arbeitsplätze aus Azure für Benutzer bereit.',
            'azure sql database' => 'ist ein verwalteter relationaler Datenbankdienst auf Basis von SQL Server in Azure.',
            'azure container registry' => 'speichert und verwaltet Container-Images für Deployments in Cloud- und DevOps-Prozessen.',
            'azure kubernetes service' => 'ist der verwaltete Kubernetes-Dienst in Azure für containerisierte Anwendungen und Clusterbetrieb.',
            'azure front door' => 'beschleunigt und schützt globale Webanwendungen über Routing, Caching und Edge-Security.',
            'application gateway' => 'ist ein Layer-7-Load-Balancer mit Routing, SSL-Terminierung und Web Application Firewall.',
            'azure bastion' => 'ermöglicht sicheren RDP- und SSH-Zugriff auf Azure-VMs ohne öffentliche Zielports.',
            'managed identity' => 'gibt Azure-Ressourcen eine verwaltete Identität für authentifizierten Zugriff ohne harte Secrets.',
            'azure landing zone' => 'bezeichnet die strukturierte Zielarchitektur für Governance, Netzwerke, Identitäten und Betriebsstandards in Azure.',
            'cost management' => 'umfasst Budgetierung, Kostenanalyse und Optimierung von Azure-Verbrauch und Reservierungen.',
            'zero trust' => 'ist ein Sicherheitsmodell, das keinem Benutzer oder Gerät pauschal vertraut und jede Anfrage kontextbezogen prüft.',
            'endpoint protection' => 'schützt Endgeräte vor Malware, schädlichem Verhalten und unerwünschten Veränderungen.',
            'security awareness' => 'umfasst Schulungen und Maßnahmen, damit Mitarbeitende Risiken erkennen und sicher handeln.',
            'backup-strategie' => 'definiert, welche Daten gesichert werden, wie oft dies geschieht und wie Wiederherstellung funktioniert.',
            'disaster recovery' => 'beschreibt Verfahren, um nach gravierenden Ausfällen Systeme und Daten geordnet wieder verfügbar zu machen.',
            'business continuity' => 'sichert kritische Geschäftsprozesse auch bei Störungen, Krisen oder Ausfällen ab.',
            'vulnerability management' => 'ist der Prozess zur Erkennung, Bewertung, Priorisierung und Behebung von Schwachstellen.',
            'patch management' => 'stellt sicher, dass Betriebssysteme, Anwendungen und Geräte zeitnah und kontrolliert aktualisiert werden.',
            'incident response' => 'umfasst die strukturierte Reaktion auf Sicherheitsvorfälle von der Erkennung bis zur Nachbereitung.',
            'siem' => 'sammelt und korreliert Sicherheitsereignisse, um Auffälligkeiten und Angriffe zentral zu erkennen.',
            'soc' => 'ist die organisatorische oder externe Einheit, die Sicherheitsmeldungen überwacht und Vorfälle bearbeitet.',
            'endpoint detection and response' => 'erkennt verdächtige Aktivitäten auf Endgeräten und unterstützt Untersuchung sowie Reaktion.',
            'extended detection and response' => 'korreliert Signale aus Endpunkten, Identitäten, E-Mail und Netzwerken zu einem erweiterten Lagebild.',
            'data loss prevention' => 'verhindert, dass sensible Informationen unkontrolliert geteilt, kopiert oder exfiltriert werden.',
            'e-mail-security' => 'schützt Postfächer und Mailverkehr vor Spam, Malware, Phishing und Identitätsmissbrauch.',
            'phishing-simulation' => 'trainiert Mitarbeitende mit realitätsnahen Kampagnen zur Erkennung betrügerischer Nachrichten.',
            'spf' => 'ist ein DNS-Eintrag, der autorisierte Mailserver für eine Domain definiert.',
            'dkim' => 'signiert ausgehende E-Mails kryptografisch, damit Empfänger ihre Echtheit prüfen können.',
            'dmarc' => 'legt fest, wie Empfänger mit SPF- und DKIM-Verstößen umgehen und liefert Berichte zur Domainnutzung.',
            'privileged access management' => 'kontrolliert besonders weitreichende Berechtigungen nach dem Prinzip minimaler Rechte.',
            'passwort-richtlinie' => 'definiert Anforderungen an Länge, Komplexität, Wiederverwendung und Handhabung von Passwörtern.',
            'compliance audit' => 'prüft systematisch, ob Prozesse, Systeme und Dokumentationen definierte Anforderungen erfüllen.',
            'iso 27001' => 'ist ein internationaler Standard für ein Informationssicherheits-Managementsystem.',
            'nis2' => 'ist die europäische Richtlinie für Cybersicherheitsanforderungen an kritische und wichtige Einrichtungen.',
            'dsgvo' => 'regelt den datenschutzkonformen Umgang mit personenbezogenen Daten in der Europäischen Union.',
            'firewall' => 'filtert Netzwerkverkehr anhand definierter Regeln und schützt Segmente oder Systeme vor unerwünschtem Zugriff.',
            'vlan' => 'trennt ein physisches Netzwerk logisch in mehrere Segmente mit eigenem Broadcast-Bereich.',
            'vpn' => 'stellt eine geschützte Verbindung zwischen Geräten, Standorten oder Benutzern über unsichere Netze her.',
            'switch management' => 'umfasst Konfiguration, Überwachung und Pflege von Switches im produktiven Netzwerk.',
            'wi-fi 6' => 'ist ein moderner WLAN-Standard mit höherer Effizienz, Kapazität und besserem Mehrbenutzerverhalten.',
            'site-to-site vpn' => 'verbindet zwei Netzstandorte dauerhaft verschlüsselt über das Internet.',
            'remote access vpn' => 'ermöglicht einzelnen Benutzern verschlüsselten Fernzugriff auf interne Ressourcen.',
            'dhcp' => 'vergibt IP-Konfigurationen wie Adresse, Gateway und DNS automatisch an Clients.',
            'dns' => 'übersetzt Namen in IP-Adressen und ist ein zentraler Basisdienst für interne und externe Erreichbarkeit.',
            'active directory' => 'ist der Verzeichnisdienst für Benutzer, Computer, Gruppen und Richtlinien in Windows-Umgebungen.',
            'domain controller' => 'stellt Kerndienste wie Authentifizierung, Gruppenrichtlinien und Verzeichnisabfragen bereit.',
            'file server' => 'bietet zentrale Dateifreigaben mit Berechtigungen, Struktur und Datensicherung.',
            'print server' => 'verwaltet Druckerwarteschlangen, Treiber und Freigaben zentral für mehrere Benutzer.',
            'network attached storage' => 'ist ein dateibasiertes Speichersystem im Netzwerk für zentrale Ablage und oft auch Backups.',
            'monitoring' => 'überwacht den Zustand von Systemen, Diensten und Netzwerken anhand definierter Messwerte und Alarme.',
            'redundanz' => 'schafft Ausfallsicherheit durch doppelt vorhandene Komponenten oder alternative Pfade.',
            'unterbrechungsfreie stromversorgung' => 'überbrückt Stromausfälle kurzzeitig und schützt Systeme vor abruptem Abschalten.',
            'server-virtualisierung' => 'ermöglicht mehrere virtuelle Server auf gemeinsamer Hardware mit besserer Auslastung und Flexibilität.',
            'hyper-v' => 'ist Microsofts Virtualisierungstechnologie für den Betrieb virtueller Maschinen.',
            'vmware' => 'bezeichnet eine weit verbreitete Virtualisierungsplattform für Server- und Infrastruktur-Workloads.',
            'patchpanel' => 'ist die strukturierte Verteilstelle für Netzwerkverkabelung im Rack oder Technikraum.',
            'netzwerksegmentierung' => 'trennt Netzbereiche, um Sicherheit, Performance und Übersichtlichkeit zu verbessern.',
            'quality of service' => 'priorisiert bestimmte Datenströme, damit zeitkritische Anwendungen stabil laufen.',
            'remote monitoring and management' => 'fasst Werkzeuge zusammen, mit denen IT-Dienstleister Systeme zentral überwachen und verwalten.',
            'it-inventarisierung' => 'erfasst Geräte, Software, Lizenzen und Zuständigkeiten als Basis für Betrieb und Planung.',
            'content management system' => 'ist eine Plattform zur strukturierten Erstellung, Pflege und Veröffentlichung von Webinhalten.',
            'responsive webdesign' => 'passt Layout und Bedienung einer Website an unterschiedliche Bildschirmgrößen und Geräte an.',
            'landingpage' => 'ist eine fokussierte Zielseite, die Besucher auf eine konkrete Aktion oder Anfrage hinführt.',
            'corporate website' => 'ist die offizielle Webpräsenz eines Unternehmens mit Informationen, Leistungen und Kontaktpunkten.',
            'conversion rate' => 'misst den Anteil der Besucher, die eine gewünschte Aktion auf einer Website ausführen.',
            'technical seo' => 'umfasst technische Voraussetzungen, damit Suchmaschinen Inhalte effizient crawlen und verstehen können.',
            'onpage seo' => 'bezeichnet inhaltliche und strukturelle Optimierungen direkt auf der Website.',
            'local seo' => 'verbessert die Sichtbarkeit eines Unternehmens in ortsbezogenen Suchanfragen.',
            'schema markup' => 'liefert strukturierte Daten, damit Suchmaschinen Inhalte besser interpretieren können.',
            'core web vitals' => 'sind Kennzahlen für Ladezeit, Interaktivität und visuelle Stabilität einer Website.',
            'page speed' => 'beschreibt, wie schnell Seiteninhalte geladen und nutzbar werden.',
            'ssl-zertifikat' => 'verschlüsselt die Verbindung zwischen Browser und Website und schafft Vertrauenssignale.',
            'content-strategie' => 'plant Themen, Formate und Zielgruppen systematisch für digitale Inhalte.',
            'keyword-recherche' => 'ermittelt Suchbegriffe, Themencluster und Suchintentionen als Basis für Content und SEO.',
            'meta description' => 'ist der kurze Beschreibungstext einer Seite in den Suchergebnissen.',
            'title tag' => 'ist der Seitentitel im Browser und eines der wichtigsten SEO-Signale für Suchmaschinen.',
            'redirect management' => 'steuert Weiterleitungen sauber, damit Nutzer und Suchmaschinen auf gültige Ziele gelangen.',
            'xml sitemap' => 'listet wichtige URLs strukturiert auf, damit Suchmaschinen sie leichter finden können.',
            'robots.txt' => 'gibt Crawlern Hinweise, welche Bereiche einer Website sie abrufen dürfen oder meiden sollen.',
            'tracking setup' => 'umfasst die technische Einrichtung von Analyse- und Conversion-Messung auf einer Website.',
            'analytics dashboard' => 'verdichtet Kennzahlen aus Tracking und Marketing in einer übersichtlichen Auswertung.',
            'kontaktformular' => 'ist ein strukturierter Einstiegspunkt für Anfragen, Leads oder Supportmeldungen auf der Website.',
            'barrierefreiheit im web' => 'stellt sicher, dass digitale Inhalte auch für Menschen mit Einschränkungen nutzbar sind.',
            'bildoptimierung' => 'verbessert Dateigröße, Formate und Auslieferung von Bildern für Performance und SEO.',
            'blog-strategie' => 'verbindet Themenplanung, Veröffentlichungsrhythmus und Suchintention für nachhaltigen Content-Aufbau.',
            'it helpdesk' => 'ist die zentrale Anlaufstelle für Benutzeranfragen, Störungen und Standardservices.',
            'ticket-system' => 'erfasst, priorisiert und dokumentiert Supportvorgänge nachvollziehbar in einem Workflow.',
            'service level agreement' => 'definiert messbare Reaktions- und Lösungszeiten sowie Serviceumfang zwischen Anbieter und Kunde.',
            'remote support' => 'ermöglicht Fernunterstützung auf Endgeräten, ohne vor Ort eingreifen zu müssen.',
            'onboarding-prozess' => 'beschreibt die strukturierte Bereitstellung von Konten, Geräten und Informationen für neue Mitarbeitende.',
            'offboarding-prozess' => 'sorgt dafür, dass Zugriffe, Geräte und Daten beim Austritt geordnet entzogen und dokumentiert werden.',
            'device lifecycle' => 'umfasst Beschaffung, Betrieb, Austausch und Entsorgung von Endgeräten.',
            'asset management' => 'verwaltet IT-Ressourcen inklusive Bestand, Zustand, Kosten und Verantwortlichkeiten.',
            'knowledgebase' => 'ist eine strukturierte Wissenssammlung für wiederkehrende Fragen, Prozesse und Standards.',
            'standard operating procedure' => 'ist eine verbindliche Arbeitsanweisung für wiederkehrende Abläufe mit klaren Schritten.',
            'workflow-automatisierung' => 'reduziert manuelle Tätigkeiten durch definierte Regeln, Trigger und Folgeschritte.',
            'freigabe-workflow' => 'ordnet Anträge oder Änderungen einem nachvollziehbaren Prüf- und Genehmigungsprozess zu.',
            'self-service-portal' => 'gibt Benutzern die Möglichkeit, Standardanliegen eigenständig anzustoßen oder zu lösen.',
            'monitoring-alert' => 'ist die automatische Meldung bei definierten Schwellwerten oder Störungen.',
            'wartungsfenster' => 'ist ein geplanter Zeitraum für Änderungen, Updates oder Eingriffe mit kalkulierter Auswirkung.',
            'rollout-plan' => 'legt Ablauf, Reihenfolge, Kommunikation und Rückfalloptionen für eine Einführung fest.',
            'change management' => 'steuert Änderungen so, dass Risiken, Freigaben und Kommunikation berücksichtigt werden.',
            'dokumentationsstandard' => 'legt fest, wie Systeme, Prozesse und Konfigurationen einheitlich dokumentiert werden.',
            'passwort-reset' => 'setzt ein Konto auf neue Zugangsdaten zurück, wenn Benutzer keinen Zugriff mehr haben.',
            'user provisioning' => 'stellt Benutzerkonten, Berechtigungen und Ressourcen regelbasiert bereit.',
            'lizenzmanagement' => 'verwaltet Zuweisung, Nutzung und Kosten von Software- und Cloud-Lizenzen.',
            'backup-check' => 'prüft regelmäßig, ob Sicherungen erfolgreich erstellt wurden und Wiederherstellung realistisch ist.',
            'update ring' => 'steuert, in welcher Reihenfolge Geräte oder Benutzer Updates erhalten.',
            'eskalationsmatrix' => 'beschreibt Zuständigkeiten und Wege, wenn Tickets oder Vorfälle eine höhere Priorität erreichen.',
            'managed service' => 'bezeichnet einen dauerhaft betreuten IT-Service mit klar definiertem Leistungsumfang.',
            default => sprintf('ist ein relevanter Fachbegriff aus dem Bereich %s, der im operativen Alltag sauber definiert und einheitlich verwendet werden sollte.', $area),
        };
    }

    /**
     * @return array<int, string>
     */
    private function useCasesFor(string $title, string $area): array
    {
        return match ($area) {
            'M365' => [
                $title . ' sauber im Tenant-Kontext einordnen und Zuständigkeiten dokumentieren.',
                'Zusammenspiel mit Identitäten, Berechtigungen und Benutzerkommunikation bewerten.',
                'Administrations-, Support- und Schulungsaufwand für Fachbereiche nachvollziehbar machen.',
            ],
            'Azure & Cloud' => [
                $title . ' in Architektur, Betrieb und Governance einer Azure-Umgebung korrekt einordnen.',
                'Abhängigkeiten zu Netzwerk, Identität, Monitoring und Kosten frühzeitig sichtbar machen.',
                'Bereitstellung, Betrieb und Notfallkonzepte technisch sauber dokumentieren.',
            ],
            'Security & Compliance' => [
                $title . ' in Sicherheitsrichtlinien, Audits und Awareness-Maßnahmen verständlich verankern.',
                'Risiken, Verantwortlichkeiten und Prüfpfade im Tagesgeschäft klar benennen.',
                'Nachweise für Schutzbedarf, Kontrollen und regulatorische Anforderungen vorbereiten.',
            ],
            'Infrastruktur & Netzwerk' => [
                $title . ' in Netzplänen, Betriebsdokumentation und Störungsanalyse nachvollziehbar beschreiben.',
                'Abhängigkeiten zu Verfügbarkeit, Segmentierung und Standardkonfigurationen festhalten.',
                'Support und Rollouts mit klaren Betriebsinformationen entlasten.',
            ],
            'Websites & SEO' => [
                $title . ' in Website-Konzept, Content-Aufbau und SEO-Maßnahmen sauber verorten.',
                'Auswirkungen auf Sichtbarkeit, Conversion und Nutzerführung verständlich machen.',
                'Technische und redaktionelle Aufgaben für Umsetzung und Pflege strukturieren.',
            ],
            'Support & Automatisierung' => [
                $title . ' in Serviceprozessen, Übergaben und Standards verständlich dokumentieren.',
                'Wiederkehrende Arbeitsschritte, Eskalationen und Automatisierungsregeln konsistent beschreiben.',
                'Benutzer, Support und Fachbereiche auf denselben Wissensstand bringen.',
            ],
            default => [
                $title . ' verständlich erklären und in der Dokumentation einheitlich verwenden.',
                'Bezug zu Prozessen, Verantwortlichkeiten und technischen Abhängigkeiten festhalten.',
                'Den Begriff für Support, Schulung und interne Verlinkung nutzbar machen.',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    private function attentionPointsFor(string $title, string $area): array
    {
        return match ($area) {
            'M365' => [
                'Lizenzierung, Berechtigungen und Datenstandorte nicht losgelöst vom Begriff betrachten.',
                'Benutzerführung und Governance früh mitdenken, statt nur die technische Funktion zu beschreiben.',
                'Abhängigkeiten zu Entra ID, Compliance und Endgeräten sauber dokumentieren.',
            ],
            'Azure & Cloud' => [
                'Kosten, Verantwortlichkeiten und Netzwerkgrenzen bereits in der Grundbeschreibung berücksichtigen.',
                'Betriebsdaten, Logging und Wiederherstellung nicht erst nach dem Go-live ergänzen.',
                'Architekturbegriffe immer im Zusammenspiel mit Security und Governance erklären.',
            ],
            'Security & Compliance' => [
                'Schutzmaßnahmen nicht isoliert, sondern im Kontext von Prozessen und Rollen erklären.',
                'Regulatorische Begriffe mit konkreten Nachweisen, Kontrollen und Verantwortlichen verbinden.',
                'Technische und organisatorische Maßnahmen klar voneinander unterscheiden.',
            ],
            'Infrastruktur & Netzwerk' => [
                'Topologie, Standardwerte und Redundanzkonzepte nicht nur implizit voraussetzen.',
                'Änderungen immer mit Betriebsauswirkung und Rückfalloption dokumentieren.',
                'Den Begriff mit Monitoring, Support und physischer Umgebung verknüpfen.',
            ],
            'Websites & SEO' => [
                'SEO-Begriffe nicht nur marketingseitig, sondern technisch und redaktionell vollständig erklären.',
                'Messbarkeit, Verantwortlichkeit und Pflegeaufwand direkt mitdenken.',
                'Auswirkungen auf Nutzererlebnis und Conversion nicht von der Technik trennen.',
            ],
            'Support & Automatisierung' => [
                'Abläufe nur dann standardisieren, wenn Zuständigkeiten und Ausnahmen klar definiert sind.',
                'Automatisierung immer mit Fallback, Monitoring und Dokumentation kombinieren.',
                'Prozesswissen so schreiben, dass es auch für Vertretung und Onboarding nutzbar bleibt.',
            ],
            default => [
                'Den Begriff nicht nur benennen, sondern in seinen Auswirkungen auf Betrieb und Prozesse erklären.',
                'Verantwortlichkeiten, Prüfpfade und Abhängigkeiten früh im Text sichtbar machen.',
                'Die Beschreibung so formulieren, dass Support und Fachbereich denselben Begriffsumfang nutzen.',
            ],
        };
    }

    private function practiceNoteFor(string $title, string $area, string $areaDescription): string
    {
        return match ($area) {
            'M365' => sprintf('Dokumentiere bei %s immer, welcher Dienst betroffen ist, wer administriert und welche Benutzergruppen oder Richtlinien beteiligt sind. So wird aus einem Buzzword ein belastbarer Betriebsbegriff.', $title),
            'Azure & Cloud' => sprintf('Lege bei %s fest, in welcher Subscription und Resource Group der Dienst liegt, wie er überwacht wird und welche Kosten- oder Sicherheitsvorgaben gelten. Gerade in %s verhindert das spätere Blindflüge.', $title, $areaDescription),
            'Security & Compliance' => sprintf('Verknüpfe %s mit konkreten Kontrollen, Nachweisen und Eskalationswegen. Erst wenn klar ist, wer prüft und was im Ereignisfall passiert, wird der Begriff im Alltag wirklich belastbar.', $title),
            'Infrastruktur & Netzwerk' => sprintf('Halte bei %s fest, wo die Komponente sitzt, welche Abhängigkeiten bestehen und wie Störungen erkannt werden. Das spart im Incident Zeit und reduziert Rückfragen im Betrieb.', $title),
            'Websites & SEO' => sprintf('Beschreibe bei %s nicht nur das Ziel, sondern auch Messgrößen, Zuständigkeiten und Pflegeprozesse. So bleibt der Begriff im Webprojekt dauerhaft anschlussfähig statt einmalig aufgeschrieben.', $title),
            'Support & Automatisierung' => sprintf('Ergänze zu %s immer Auslöser, zuständige Rolle, gewünschtes Ergebnis und Eskalation. Genau diese vier Punkte entscheiden, ob ein Prozess später zuverlässig wiederholbar ist.', $title),
            default => sprintf('Ergänze zu %s konkrete Zuständigkeiten, typische Beispiele und angrenzende Prozesse. Dadurch wird der Eintrag von einer Definition zu einer nutzbaren Arbeitsgrundlage.', $title),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function definitions(): array
    {
        return [
            'm365' => [
                'label' => 'M365',
                'description' => 'Microsoft-365-Plattform für Zusammenarbeit, Identitäten, Geräte und Governance.',
                'accent' => '#0EA5E9',
                'entries' => [
                    ['title' => 'Microsoft 365 Tenant', 'synonyms' => ['M365 Tenant', 'Office 365 Tenant']],
                    ['title' => 'Exchange Online', 'synonyms' => ['EXO']],
                    ['title' => 'SharePoint Online', 'synonyms' => ['SPO']],
                    ['title' => 'Microsoft Teams', 'keyword' => 'Teams'],
                    ['title' => 'OneDrive for Business', 'synonyms' => ['OneDrive Business']],
                    ['title' => 'Microsoft Entra ID', 'synonyms' => ['Entra ID', 'Azure AD']],
                    'Microsoft Intune',
                    'Conditional Access',
                    ['title' => 'Multi-Faktor-Authentifizierung', 'synonyms' => ['MFA', 'Mehr-Faktor-Authentifizierung']],
                    'Defender for Office 365',
                    'Teams Telefonie',
                    'Microsoft Planner',
                    'Microsoft To Do',
                    'Power Automate',
                    'Power Apps',
                    'Microsoft Forms',
                    'Microsoft Bookings',
                    'Microsoft Loop',
                    'Viva Engage',
                    'Viva Insights',
                    'Microsoft Purview',
                    'Aufbewahrungsrichtlinien',
                    'Sensitivity Labels',
                    ['title' => 'Copilot for Microsoft 365', 'synonyms' => ['M365 Copilot']],
                    'Hybrid Identity',
                ],
            ],
            'azure-cloud' => [
                'label' => 'Azure & Cloud',
                'description' => 'Azure-Dienste, Cloud-Betrieb, Governance und skalierbare Plattform-Bausteine.',
                'accent' => '#2563EB',
                'entries' => [
                    'Azure Subscription',
                    'Resource Group',
                    'Virtual Network',
                    ['title' => 'Network Security Group', 'synonyms' => ['NSG']],
                    'Azure App Service',
                    'Azure Functions',
                    'Azure Storage Account',
                    'Azure Blob Storage',
                    'Azure Files',
                    'Azure Backup',
                    'Azure Site Recovery',
                    'Azure Key Vault',
                    'Azure Monitor',
                    'Log Analytics',
                    'Azure Policy',
                    'Azure Virtual Desktop',
                    'Azure SQL Database',
                    'Azure Container Registry',
                    ['title' => 'Azure Kubernetes Service', 'synonyms' => ['AKS']],
                    'Azure Front Door',
                    'Application Gateway',
                    'Azure Bastion',
                    'Managed Identity',
                    'Azure Landing Zone',
                    'Cost Management',
                ],
            ],
            'security-compliance' => [
                'label' => 'Security & Compliance',
                'description' => 'Informationssicherheit, Schutzmaßnahmen, Audits und regulatorische Anforderungen.',
                'accent' => '#DC2626',
                'entries' => [
                    'Zero Trust',
                    'Endpoint Protection',
                    'Security Awareness',
                    'Backup-Strategie',
                    'Disaster Recovery',
                    'Business Continuity',
                    'Vulnerability Management',
                    'Patch Management',
                    'Incident Response',
                    'SIEM',
                    'SOC',
                    ['title' => 'Endpoint Detection and Response', 'synonyms' => ['EDR']],
                    ['title' => 'Extended Detection and Response', 'synonyms' => ['XDR']],
                    ['title' => 'Data Loss Prevention', 'synonyms' => ['DLP']],
                    'E-Mail-Security',
                    'Phishing-Simulation',
                    'SPF',
                    'DKIM',
                    'DMARC',
                    'Privileged Access Management',
                    'Passwort-Richtlinie',
                    'Compliance Audit',
                    'ISO 27001',
                    'NIS2',
                    'DSGVO',
                ],
            ],
            'infrastructure-network' => [
                'label' => 'Infrastruktur & Netzwerk',
                'description' => 'Netzwerke, Server, Verfügbarkeit und technische Betriebsgrundlagen.',
                'accent' => '#7C3AED',
                'entries' => [
                    'Firewall',
                    'VLAN',
                    'VPN',
                    'Switch Management',
                    'Wi-Fi 6',
                    'Site-to-Site VPN',
                    'Remote Access VPN',
                    'DHCP',
                    'DNS',
                    'Active Directory',
                    'Domain Controller',
                    'File Server',
                    'Print Server',
                    ['title' => 'Network Attached Storage', 'synonyms' => ['NAS']],
                    'Monitoring',
                    'Redundanz',
                    ['title' => 'Unterbrechungsfreie Stromversorgung', 'synonyms' => ['USV']],
                    'Server-Virtualisierung',
                    'Hyper-V',
                    'VMware',
                    'Patchpanel',
                    'Netzwerksegmentierung',
                    ['title' => 'Quality of Service', 'synonyms' => ['QoS']],
                    ['title' => 'Remote Monitoring and Management', 'synonyms' => ['RMM']],
                    'IT-Inventarisierung',
                ],
            ],
            'web-seo' => [
                'label' => 'Websites & SEO',
                'description' => 'Webprojekte, Sichtbarkeit, technische Optimierung und digitale Inhalte.',
                'accent' => '#0F766E',
                'entries' => [
                    ['title' => 'Content Management System', 'synonyms' => ['CMS']],
                    'Responsive Webdesign',
                    'Landingpage',
                    'Corporate Website',
                    'Conversion Rate',
                    'Technical SEO',
                    'OnPage SEO',
                    'Local SEO',
                    'Schema Markup',
                    'Core Web Vitals',
                    'Page Speed',
                    'SSL-Zertifikat',
                    'Content-Strategie',
                    'Keyword-Recherche',
                    'Meta Description',
                    'Title Tag',
                    'Redirect Management',
                    'XML Sitemap',
                    'robots.txt',
                    'Tracking Setup',
                    'Analytics Dashboard',
                    'Kontaktformular',
                    'Barrierefreiheit im Web',
                    'Bildoptimierung',
                    'Blog-Strategie',
                ],
            ],
            'support-automation' => [
                'label' => 'Support & Automatisierung',
                'description' => 'Service-Prozesse, Betriebsmodelle, Automatisierung und wiederholbare Abläufe.',
                'accent' => '#F59E0B',
                'entries' => [
                    'IT Helpdesk',
                    'Ticket-System',
                    ['title' => 'Service Level Agreement', 'synonyms' => ['SLA']],
                    'Remote Support',
                    'Onboarding-Prozess',
                    'Offboarding-Prozess',
                    'Device Lifecycle',
                    'Asset Management',
                    'Knowledgebase',
                    ['title' => 'Standard Operating Procedure', 'synonyms' => ['SOP']],
                    'Workflow-Automatisierung',
                    'Freigabe-Workflow',
                    'Self-Service-Portal',
                    'Monitoring-Alert',
                    'Wartungsfenster',
                    'Rollout-Plan',
                    'Change Management',
                    'Dokumentationsstandard',
                    'Passwort-Reset',
                    'User Provisioning',
                    'Lizenzmanagement',
                    'Backup-Check',
                    'Update Ring',
                    'Eskalationsmatrix',
                    'Managed Service',
                ],
            ],
        ];
    }
}
