<?php
/**
 * CMS Forum – Pagination Helper
 *
 * Erzeugt Paginierungsdaten und HTML für Forum-Listen.
 *
 * @package CMS_Forum\Helpers
 */

declare(strict_types=1);

namespace CMS_Forum\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class Pagination
{
    public readonly int $currentPage;
    public readonly int $totalItems;
    public readonly int $perPage;
    public readonly int $totalPages;
    public readonly int $offset;

    public function __construct(int $totalItems, int $currentPage = 1, int $perPage = 20)
    {
        $this->totalItems  = max(0, $totalItems);
        $this->perPage     = max(1, $perPage);
        $this->totalPages  = max(1, (int) ceil($this->totalItems / $this->perPage));
        $this->currentPage = max(1, min($currentPage, $this->totalPages));
        $this->offset      = ($this->currentPage - 1) * $this->perPage;
    }

    /**
     * Braucht die Seite eine Paginierung?
     */
    public function needsPagination(): bool
    {
        return $this->totalPages > 1;
    }

    /**
     * Hat die aktuelle Seite eine vorherige Seite?
     */
    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Hat die aktuelle Seite eine nächste Seite?
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Seitenarray mit Auslassungspunkten erzeugen.
     *
     * @return array<int|string> z.B. [1, '…', 4, 5, 6, '…', 10]
     */
    public function getPages(int $adjacents = 2): array
    {
        $pages = [];
        $total = $this->totalPages;
        $curr  = $this->currentPage;

        if ($total <= (2 * $adjacents + 5)) {
            // Alle Seiten anzeigen
            for ($i = 1; $i <= $total; $i++) {
                $pages[] = $i;
            }
            return $pages;
        }

        // Erste Seite
        $pages[] = 1;

        // Linke Auslassung
        if ($curr - $adjacents > 3) {
            $pages[] = '…';
        } elseif ($curr - $adjacents === 3) {
            $pages[] = 2;
        }

        // Mittlere Seiten
        $start = max(2, $curr - $adjacents);
        $end   = min($total - 1, $curr + $adjacents);

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        // Rechte Auslassung
        if ($curr + $adjacents < $total - 2) {
            $pages[] = '…';
        } elseif ($curr + $adjacents === $total - 2) {
            $pages[] = $total - 1;
        }

        // Letzte Seite
        $pages[] = $total;

        return $pages;
    }

    /**
     * URL für eine bestimmte Seite erzeugen.
     */
    public static function buildUrl(string $baseUrl, int $page): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        return $baseUrl . $separator . 'page=' . $page;
    }

    /**
     * Paginierungs-HTML rendern.
     */
    public function render(string $baseUrl): string
    {
        if (!$this->needsPagination()) {
            return '';
        }

        $html = '<nav class="cmsforum-pagination" aria-label="Seitennavigation"><ul>';

        // Zurück
        if ($this->hasPrev()) {
            $html .= '<li><a href="' . htmlspecialchars(self::buildUrl($baseUrl, $this->currentPage - 1)) . '" class="cmsforum-pagination__prev" aria-label="Vorherige Seite">&laquo;</a></li>';
        } else {
            $html .= '<li><span class="cmsforum-pagination__prev cmsforum-pagination--disabled" aria-hidden="true">&laquo;</span></li>';
        }

        // Seitenzahlen
        foreach ($this->getPages() as $page) {
            if ($page === '…') {
                $html .= '<li><span class="cmsforum-pagination__ellipsis">&hellip;</span></li>';
            } elseif ($page === $this->currentPage) {
                $html .= '<li><span class="cmsforum-pagination__current" aria-current="page">' . $page . '</span></li>';
            } else {
                $html .= '<li><a href="' . htmlspecialchars(self::buildUrl($baseUrl, (int) $page)) . '">' . $page . '</a></li>';
            }
        }

        // Weiter
        if ($this->hasNext()) {
            $html .= '<li><a href="' . htmlspecialchars(self::buildUrl($baseUrl, $this->currentPage + 1)) . '" class="cmsforum-pagination__next" aria-label="Nächste Seite">&raquo;</a></li>';
        } else {
            $html .= '<li><span class="cmsforum-pagination__next cmsforum-pagination--disabled" aria-hidden="true">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
