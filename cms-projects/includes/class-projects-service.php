<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Projects_Service
{
    private const MAX_SLUG_LENGTH = 120;
    private const MAX_SHORT_TEXT_LENGTH = 190;
    private const MAX_SUMMARY_LENGTH = 1200;
    private const MAX_DESCRIPTION_LENGTH = 20000;

    public function __construct(private readonly CMS_Projects_Repository $repository)
    {
    }

    public function boot(): void
    {
        $this->repository->ensureTables();
    }

    public function getSummary(): array
    {
        $projects = $this->repository->getProjects();
        $active = 0;
        $public = 0;

        foreach ($projects as $project) {
            if (($project['status'] ?? '') === 'active') {
                $active++;
            }
            if (($project['visibility'] ?? '') === 'public') {
                $public++;
            }
        }

        return [
            'projects' => $this->repository->countProjects(),
            'boards' => $this->repository->countBoards(),
            'widgets' => $this->repository->countWidgets(),
            'active_projects' => $active,
            'public_projects' => $public,
        ];
    }

    public function getBoardTypes(): array
    {
        return [
            'kanban' => ['label' => 'Kanban', 'description' => 'Backlog, laufende Arbeit und Übergaben im Sprintfluss.'],
            'diy' => ['label' => 'DIY', 'description' => 'Selbstbau-, Aufgaben- und Material-Tracking mit klaren Arbeitspaketen.'],
            'miro' => ['label' => 'MIRO', 'description' => 'Freies Ideen- und Whiteboard-Cluster für Workshops und Brainstorming.'],
            'flow' => ['label' => 'FLOW', 'description' => 'Kontinuierlicher Durchsatz mit Fokus auf Blocker und Engpässe.'],
            'roadmap' => ['label' => 'Roadmap', 'description' => 'Zeitliche Planung nach Quartalen, Phasen oder Releases.'],
        ];
    }

    public function getWidgetTypes(): array
    {
        return [
            'text' => ['label' => 'Text', 'description' => 'Freier Beschreibungstext oder Status-Update.'],
            'checklist' => ['label' => 'Checkliste', 'description' => 'Abhakbare Listen für Deliverables und To-dos.'],
            'links' => ['label' => 'Links', 'description' => 'Schnellzugriffe auf Dokumente, Tickets und Ressourcen.'],
            'stats' => ['label' => 'Kennzahlen', 'description' => 'Projektmetriken wie Fortschritt, Budget oder offene Punkte.'],
            'timeline' => ['label' => 'Timeline', 'description' => 'Wichtige Meilensteine und Termine.'],
        ];
    }

    public function getProjectStatuses(): array
    {
        return ['draft' => 'Entwurf', 'active' => 'Aktiv', 'paused' => 'Pausiert', 'archived' => 'Archiviert'];
    }

    public function getProjectVisibilities(): array
    {
        return ['public' => 'Public', 'member' => 'Member', 'private' => 'Privat'];
    }

    public function getWidgetScopes(): array
    {
        return ['public' => 'Nur Public', 'member' => 'Nur Member', 'both' => 'Public + Member'];
    }

    public function getProjects(): array
    {
        $projects = $this->repository->getProjects();
        return $this->decorateProjects($projects);
    }

    public function getPublicProjects(): array
    {
        $projects = $this->repository->getPublicProjects();
        return $this->decorateProjects($projects);
    }

    public function getMemberProjects(): array
    {
        $projects = $this->repository->getMemberProjects();
        return $this->decorateProjects($projects);
    }

    public function findProject(int $id): ?array
    {
        $project = $this->repository->findProjectById($id);
        return $project !== null ? $this->decorateProject($project) : null;
    }

    public function findProjectBySlug(string $slug): ?array
    {
        $project = $this->repository->findProjectBySlug($this->normalizeSlug($slug));
        return $project !== null ? $this->decorateProject($project) : null;
    }

    public function getProjectBoards(int $projectId, string $scope = 'admin'): array
    {
        $scope = $this->normalizeDashboardScope($scope);
        $boards = $this->repository->getBoardsByProject($projectId, $scope === 'admin', $scope === 'public' ? true : null);
        return array_map(function (array $board): array {
            $board['payload_data'] = $this->sanitizeBoardPayload($this->decodePayload((string) ($board['payload'] ?? '')));
            $typeConfig = $this->getBoardTypes()[$board['board_type'] ?? 'kanban'] ?? ['label' => 'Board', 'description' => ''];
            $board['board_label'] = (string) ($typeConfig['label'] ?? 'Board');
            return $board;
        }, $boards);
    }

    public function getProjectWidgets(int $projectId, string $scope = 'admin'): array
    {
        $scope = $this->normalizeDashboardScope($scope);
        $widgets = $this->repository->getWidgetsByProject($projectId, $scope, $scope === 'admin');
        return array_map(function (array $widget): array {
            $widget['payload_data'] = $this->sanitizeWidgetPayload((string) ($widget['widget_type'] ?? 'text'), $this->decodePayload((string) ($widget['payload'] ?? '')));
            $widgetType = $this->getWidgetTypes()[$widget['widget_type'] ?? 'text'] ?? ['label' => 'Widget', 'description' => ''];
            $widget['widget_label'] = (string) ($widgetType['label'] ?? 'Widget');
            return $widget;
        }, $widgets);
    }

    public function getProjectDashboardPayload(int $projectId, string $scope): ?array
    {
        $scope = $this->normalizeDashboardScope($scope);
        $project = $this->findProject($projectId);
        if ($project === null) {
            return null;
        }

        if (!$this->canAccessProjectScope($project, $scope)) {
            return null;
        }

        return [
            'project' => $project,
            'boards' => $this->getProjectBoards($projectId, $scope),
            'widgets' => $this->getProjectWidgets($projectId, $scope),
        ];
    }

    public function getProjectDefaults(): array
    {
        return [
            'id' => 0,
            'name' => '',
            'slug' => '',
            'summary' => '',
            'description' => '',
            'status' => 'active',
            'visibility' => 'member',
            'owner_name' => '',
            'accent_color' => '#2563eb',
            'member_headline' => 'Projekt-Dashboard für Mitglieder',
            'public_headline' => 'Öffentliche Projektübersicht',
        ];
    }

    public function saveProject(array $input, ?int $id = null): array
    {
        $name = $this->sanitizeText((string) ($input['name'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
        $slug = $this->normalizeSlug((string) ($input['slug'] ?? ''));
        if ($name === '') {
            return ['success' => false, 'message' => 'Projektname ist erforderlich.'];
        }

        if ($slug === '') {
            $slug = $this->normalizeSlug($name);
        }

        if ($slug === '') {
            return ['success' => false, 'message' => 'Für das Projekt konnte kein gültiger Slug erzeugt werden.'];
        }

        $existing = $this->findProjectBySlug($slug);
        if ($existing !== null && $id !== (int) ($existing['id'] ?? 0)) {
            return ['success' => false, 'message' => 'Der Projekt-Slug ist bereits vergeben.'];
        }

        $status = (string) ($input['status'] ?? 'active');
        if (!isset($this->getProjectStatuses()[$status])) {
            $status = 'active';
        }

        $visibility = (string) ($input['visibility'] ?? 'member');
        if (!isset($this->getProjectVisibilities()[$visibility])) {
            $visibility = 'member';
        }

        $saveId = $this->repository->saveProject([
            'name' => $name,
            'slug' => $slug,
            'summary' => $this->sanitizeMultilineText((string) ($input['summary'] ?? ''), self::MAX_SUMMARY_LENGTH),
            'description' => $this->sanitizeMultilineText((string) ($input['description'] ?? ''), self::MAX_DESCRIPTION_LENGTH),
            'status' => $status,
            'visibility' => $visibility,
            'owner_name' => $this->sanitizeText((string) ($input['owner_name'] ?? ''), self::MAX_SHORT_TEXT_LENGTH),
            'accent_color' => $this->normalizeColor((string) ($input['accent_color'] ?? '#2563eb')),
            'member_headline' => $this->sanitizeText((string) ($input['member_headline'] ?? ''), self::MAX_SHORT_TEXT_LENGTH),
            'public_headline' => $this->sanitizeText((string) ($input['public_headline'] ?? ''), self::MAX_SHORT_TEXT_LENGTH),
        ], $id);

        if ($saveId === false) {
            return ['success' => false, 'message' => 'Projekt konnte nicht gespeichert werden.'];
        }

        return ['success' => true, 'message' => 'Projekt wurde gespeichert.', 'id' => (int) $saveId];
    }

    public function saveBoard(array $input): array
    {
        $projectId = (int) ($input['project_id'] ?? 0);
        $project = $this->findProject($projectId);
        if ($project === null) {
            return ['success' => false, 'message' => 'Projekt für das Board wurde nicht gefunden.'];
        }

        $boardType = (string) ($input['board_type'] ?? 'kanban');
        if (!isset($this->getBoardTypes()[$boardType])) {
            $boardType = 'kanban';
        }

        $title = $this->sanitizeText((string) ($input['title'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
        if ($title === '') {
            return ['success' => false, 'message' => 'Board-Titel ist erforderlich.'];
        }

        $payload = trim((string) ($input['payload'] ?? ''));
        if ($payload === '') {
            $payload = json_encode($this->getDefaultBoardPayload($boardType), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $decoded = $this->parsePayload($payload);
        if ($decoded === null) {
            return ['success' => false, 'message' => 'Board-Payload muss gültiges JSON sein.'];
        }

        $decoded = $this->sanitizeBoardPayload($decoded);

        $saveId = $this->repository->saveBoard([
            'project_id' => $projectId,
            'board_type' => $boardType,
            'title' => $title,
            'description' => $this->sanitizeMultilineText((string) ($input['description'] ?? ''), self::MAX_SUMMARY_LENGTH),
            'position' => max(0, (int) ($input['position'] ?? 0)),
            'is_public' => $this->toBooleanFlag($input['is_public'] ?? 0),
            'is_active' => $this->toBooleanFlag($input['is_active'] ?? 0),
            'payload' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if ($saveId === false) {
            return ['success' => false, 'message' => 'Board konnte nicht gespeichert werden.'];
        }

        return ['success' => true, 'message' => 'Board wurde gespeichert.', 'id' => (int) $saveId];
    }

    public function saveWidget(array $input): array
    {
        $projectId = (int) ($input['project_id'] ?? 0);
        $project = $this->findProject($projectId);
        if ($project === null) {
            return ['success' => false, 'message' => 'Projekt für das Widget wurde nicht gefunden.'];
        }

        $widgetType = (string) ($input['widget_type'] ?? 'text');
        if (!isset($this->getWidgetTypes()[$widgetType])) {
            $widgetType = 'text';
        }

        $scope = (string) ($input['scope'] ?? 'both');
        if (!isset($this->getWidgetScopes()[$scope])) {
            $scope = 'both';
        }

        $title = $this->sanitizeText((string) ($input['title'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
        if ($title === '') {
            return ['success' => false, 'message' => 'Widget-Titel ist erforderlich.'];
        }

        $payload = trim((string) ($input['payload'] ?? ''));
        if ($payload === '') {
            $payload = json_encode($this->getDefaultWidgetPayload($widgetType), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $decoded = $this->parsePayload($payload);
        if ($decoded === null) {
            return ['success' => false, 'message' => 'Widget-Payload muss gültiges JSON sein.'];
        }

        $decoded = $this->sanitizeWidgetPayload($widgetType, $decoded);

        $saveId = $this->repository->saveWidget([
            'project_id' => $projectId,
            'scope' => $scope,
            'widget_type' => $widgetType,
            'title' => $title,
            'content' => $this->sanitizeMultilineText((string) ($input['content'] ?? ''), self::MAX_DESCRIPTION_LENGTH),
            'position' => max(0, (int) ($input['position'] ?? 0)),
            'is_active' => $this->toBooleanFlag($input['is_active'] ?? 0),
            'payload' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if ($saveId === false) {
            return ['success' => false, 'message' => 'Widget konnte nicht gespeichert werden.'];
        }

        return ['success' => true, 'message' => 'Widget wurde gespeichert.', 'id' => (int) $saveId];
    }

    public function getProjectPreviewLinks(array $project): array
    {
        $slug = (string) ($project['slug'] ?? '');
        return [
            'public' => $slug !== '' ? '/projects/' . rawurlencode($slug) : '/projects',
            'member' => $slug !== '' ? '/member/plugin/projects?project=' . rawurlencode($slug) : '/member/plugin/projects',
        ];
    }

    private function decorateProjects(array $projects): array
    {
        if ($projects === []) {
            return [];
        }

        $counts = $this->repository->getProjectEntityCounts(array_column($projects, 'id'));

        return array_map(function (array $project) use ($counts): array {
            $projectId = (int) ($project['id'] ?? 0);
            $aggregate = $counts[$projectId] ?? ['board_count' => 0, 'widget_count' => 0];
            $project['board_count'] = (int) ($aggregate['board_count'] ?? 0);
            $project['widget_count'] = (int) ($aggregate['widget_count'] ?? 0);
            $project['preview_links'] = $this->getProjectPreviewLinks($project);
            return $project;
        }, $projects);
    }

    private function decorateProject(array $project): array
    {
        $projects = $this->decorateProjects([$project]);
        return $projects[0] ?? $project;
    }

    private function normalizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\-\_]+/', '-', $value) ?? '';
        $value = trim($value, '-_');
        $value = preg_replace('/[-_]{2,}/', '-', $value) ?? '';
        return substr($value, 0, self::MAX_SLUG_LENGTH);
    }

    private function normalizeColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1) {
            return strtolower($value);
        }
        return '#2563eb';
    }

    private function decodePayload(string $payload): array
    {
        $decoded = $this->parsePayload($payload);
        return $decoded ?? [];
    }

    private function parsePayload(string $payload): ?array
    {
        $payload = trim($payload);
        if ($payload === '') {
            return [];
        }

        try {
            $decoded = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeDashboardScope(string $scope): string
    {
        return in_array($scope, ['admin', 'member', 'public'], true) ? $scope : 'public';
    }

    private function canAccessProjectScope(array $project, string $scope): bool
    {
        $visibility = (string) ($project['visibility'] ?? 'member');
        $status = (string) ($project['status'] ?? 'draft');

        return match ($scope) {
            'admin' => true,
            'member' => in_array($visibility, ['member', 'public'], true) && in_array($status, ['active', 'paused'], true),
            default => $visibility === 'public' && $status === 'active',
        };
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        return $this->truncate($value, $maxLength);
    }

    private function sanitizeMultilineText(string $value, int $maxLength): string
    {
        $value = trim(str_replace(["\r\n", "\r"], "\n", strip_tags($value)));
        return $this->truncate($value, $maxLength);
    }

    private function truncate(string $value, int $maxLength): string
    {
        if ($maxLength <= 0) {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function sanitizeBoardPayload(array $payload): array
    {
        foreach (['columns', 'lanes', 'clusters', 'stages', 'milestones'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return [$key => $this->sanitizeBoardGroups($payload[$key])];
            }
        }

        return ['columns' => []];
    }

    private function sanitizeBoardGroups(array $groups): array
    {
        $sanitized = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $items = [];
            foreach ((array) ($group['items'] ?? []) as $item) {
                $label = $this->sanitizeText((string) $item, self::MAX_SHORT_TEXT_LENGTH);
                if ($label !== '') {
                    $items[] = $label;
                }
            }

            $title = $this->sanitizeText((string) ($group['title'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
            if ($title === '' && $items === []) {
                continue;
            }

            $sanitized[] = [
                'title' => $title !== '' ? $title : 'Block',
                'items' => $items,
            ];
        }

        return $sanitized;
    }

    private function sanitizeWidgetPayload(string $widgetType, array $payload): array
    {
        $items = [];

        foreach ((array) ($payload['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ($widgetType === 'checklist') {
                $label = $this->sanitizeText((string) ($item['label'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
                if ($label === '') {
                    continue;
                }
                $items[] = ['label' => $label, 'done' => !empty($item['done'])];
                continue;
            }

            if ($widgetType === 'links') {
                $label = $this->sanitizeText((string) ($item['label'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
                $url = $this->sanitizeUrl((string) ($item['url'] ?? ''));
                if ($label === '' || $url === '') {
                    continue;
                }
                $items[] = ['label' => $label, 'url' => $url];
                continue;
            }

            if ($widgetType === 'stats') {
                $label = $this->sanitizeText((string) ($item['label'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
                $value = $this->sanitizeText((string) ($item['value'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
                if ($label === '' && $value === '') {
                    continue;
                }
                $items[] = ['label' => $label, 'value' => $value];
                continue;
            }

            if ($widgetType === 'timeline') {
                $date = $this->sanitizeText((string) ($item['date'] ?? ''), 40);
                $label = $this->sanitizeText((string) ($item['label'] ?? ''), self::MAX_SHORT_TEXT_LENGTH);
                if ($date === '' && $label === '') {
                    continue;
                }
                $items[] = ['date' => $date, 'label' => $label];
            }
        }

        return ['items' => $items];
    }

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $validated = filter_var($url, FILTER_VALIDATE_URL);
        if (!is_string($validated)) {
            return '';
        }

        $scheme = strtolower((string) parse_url($validated, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $validated : '';
    }

    private function toBooleanFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    private function getDefaultBoardPayload(string $boardType): array
    {
        return match ($boardType) {
            'kanban' => [
                'columns' => [
                    ['title' => 'Backlog', 'items' => ['Projektziele schärfen', 'Tickets priorisieren']],
                    ['title' => 'In Arbeit', 'items' => ['Dashboard-MVP bauen', 'Widgets testen']],
                    ['title' => 'Review', 'items' => ['Stakeholder-Feedback einsammeln']],
                    ['title' => 'Done', 'items' => ['Projekt-Kickoff durchgeführt']],
                ],
            ],
            'diy' => [
                'lanes' => [
                    ['title' => 'Idee', 'items' => ['Anforderungen sammeln', 'Materialliste definieren']],
                    ['title' => 'Bauen', 'items' => ['Modul umsetzen', 'Komponenten verbinden']],
                    ['title' => 'Test', 'items' => ['Abnahme mit Team']],
                    ['title' => 'Learn', 'items' => ['Retrospektive dokumentieren']],
                ],
            ],
            'miro' => [
                'clusters' => [
                    ['title' => 'Ideen', 'items' => ['Neue Features', 'Nutzerwünsche']],
                    ['title' => 'Referenzen', 'items' => ['Wettbewerb analysieren', 'Best Practices sammeln']],
                    ['title' => 'Nächste Schritte', 'items' => ['Workshop planen']],
                ],
            ],
            'flow' => [
                'stages' => [
                    ['title' => 'Ready', 'items' => ['Stories fertig spezifiziert']],
                    ['title' => 'Doing', 'items' => ['Implementierung', 'Abstimmung mit Design']],
                    ['title' => 'Blocked', 'items' => ['Abhängigkeit zu externem API']],
                    ['title' => 'Released', 'items' => ['MVP live']],
                ],
            ],
            default => [
                'milestones' => [
                    ['title' => 'Q1', 'items' => ['Projektstart', 'Requirements']],
                    ['title' => 'Q2', 'items' => ['MVP und erste Widgets']],
                    ['title' => 'Q3', 'items' => ['Automationen und Reports']],
                    ['title' => 'Q4', 'items' => ['Rollout und Optimierung']],
                ],
            ],
        };
    }

    private function getDefaultWidgetPayload(string $widgetType): array
    {
        return match ($widgetType) {
            'checklist' => ['items' => [['label' => 'Projektbriefing freigeben', 'done' => true], ['label' => 'Stakeholder onboarden', 'done' => false], ['label' => 'Sprintziel festlegen', 'done' => false]]],
            'links' => ['items' => [['label' => 'Konzept', 'url' => 'https://example.com/konzept'], ['label' => 'Designboard', 'url' => 'https://example.com/designboard']]],
            'stats' => ['items' => [['label' => 'Fortschritt', 'value' => '68%'], ['label' => 'Offene Punkte', 'value' => '14'], ['label' => 'Sprint', 'value' => 'Sprint 04']]],
            'timeline' => ['items' => [['date' => '2026-04-01', 'label' => 'Kickoff'], ['date' => '2026-04-12', 'label' => 'MVP Review'], ['date' => '2026-04-30', 'label' => 'Release-Kandidat']]],
            default => ['items' => []],
        };
    }
}
