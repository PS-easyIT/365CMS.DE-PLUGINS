from __future__ import annotations

import csv
import json
from pathlib import Path

FILES = {
    "M365_Glossar.csv": Path(r"x:\03-Dokumente & Bilder\50-PhinIT\50-ARTIKEL\M365_Glossar.csv"),
    "Azure_Glossar.csv": Path(r"x:\03-Dokumente & Bilder\50-PhinIT\50-ARTIKEL\Azure_Glossar.csv"),
    "Infra_Netzwerk_Glossar.csv": Path(r"x:\03-Dokumente & Bilder\50-PhinIT\50-ARTIKEL\Infra_Netzwerk_Glossar.csv"),
    "Security_Compliance_Glossar.csv": Path(r"x:\03-Dokumente & Bilder\50-PhinIT\50-ARTIKEL\Security_Compliance_Glossar.csv"),
}

FIELD_MAP = {
    "Eintrag": "eintrag",
    "Kurzbeschreibung": "kurzbeschreibung",
    "Langbeschreibung": "langbeschreibung",
    "Lizenz-Info": "lizenz_info",
    "MS Learn / Weblinks": "ms_learn_weblinks",
}

ENCODINGS = ["utf-8-sig", "utf-8", "cp1252", "latin-1"]


def load_rows(path: Path) -> list[dict[str, str]]:
    last_error: Exception | None = None
    for encoding in ENCODINGS:
        try:
            with path.open("r", encoding=encoding, newline="") as handle:
                reader = csv.DictReader(handle, delimiter=';', quotechar='"')
                rows: list[dict[str, str]] = []
                for row in reader:
                    normalized = {
                        FIELD_MAP[key]: (row.get(key) or "").strip()
                        for key in FIELD_MAP
                    }
                    rows.append(normalized)
                return rows
        except Exception as exc:  # noqa: BLE001
            last_error = exc
    raise RuntimeError(f"Could not read {path}: {last_error}")


summary: dict[str, dict[str, object]] = {}
for name, path in FILES.items():
    rows = load_rows(path)
    summary[name] = {
        "file_path": str(path),
        "record_count": len(rows),
        "entries": rows,
    }

output_path = Path(r"e:\00-WPwork\365CMS.DE-PLUGINS\DataAnalysisExpert\glossar_summary.json")
output_path.write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8")
print(output_path)
