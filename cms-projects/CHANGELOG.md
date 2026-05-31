# Changelog – CMS Projects

## 3.0.2 – 2026-05-31

- Admin-Menüeintrag wird in der Core-Sidebar mit `365CMS | ` vorangestellt, damit 365CMS-Plugins gemeinsam sortiert werden.

## 3.0.1 – 2026-05-18

- PHP-Anforderung auf 8.4 angehoben und Plugin-Version auf `3.0.1` aktualisiert.
- Admin-Renderer prüft jetzt serverseitig `CMS\Auth::instance()->isAdmin()` und redirectet unberechtigte Zugriffe mit HTTP 303.
- Public-/Member-Slugs werden vor Lookups normalisiert und längenbegrenzt.
- Board-/Widget-JSON-Payloads sind auf 64 KB begrenzt, um teure Admin-Requests zu vermeiden.
- Widget-Link-URLs blockieren jetzt Kontrollzeichen, Credentials, lokale Hosts sowie private/reservierte IPs.
- Public-/Member-Templates nutzen semantischere Container und Statusrollen für Empty States.
- CSS optisch beruhigt: weniger Glassmorphism/Gradient, klarere Token-Anbindung und Reduced-Motion-Regel.

## 3.0.0 – 2026-05-17

- Kompatibilität auf 365CMS 3.0.0 angehoben.
- Public- und Member-Templates um direkte `ABSPATH`-Guards ergänzt.
- Projekt-Akzentfarben vor Ausgabe strikt als Hex-Farben validiert.
- Dynamische Akzentfarbe auf CSS-Custom-Property `--cp-project-accent` umgestellt, damit die visuelle Darstellung zentral über `style.css` läuft.
- `update.json` für den 365CMS-3.x-Updatefluss ergänzt.

## 1.0.0

- Erste Version mit Projekt-Dashboards, Boards, Widgets, Member- und Public-Ausgabe.
