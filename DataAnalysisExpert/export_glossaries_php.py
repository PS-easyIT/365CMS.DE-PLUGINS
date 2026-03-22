from __future__ import annotations

import json
from pathlib import Path

json_path = Path(r"e:\00-WPwork\365CMS.DE-PLUGINS\DataAnalysisExpert\glossar_summary.json")
php_path = Path(r"e:\00-WPwork\365CMS.DE-PLUGINS\DataAnalysisExpert\glossar_summary.php")

data = json.loads(json_path.read_text(encoding="utf-8"))


def to_php(value, indent=0):
    space = "    " * indent
    next_space = "    " * (indent + 1)
    if value is None:
        return "null"
    if isinstance(value, bool):
        return "true" if value else "false"
    if isinstance(value, (int, float)):
        return repr(value)
    if isinstance(value, str):
        escaped = value.replace("\\", "\\\\").replace("'", "\\'")
        return f"'{escaped}'"
    if isinstance(value, list):
        if not value:
            return "[]"
        items = [f"{next_space}{to_php(item, indent + 1)}," for item in value]
        return "[\n" + "\n".join(items) + f"\n{space}]"
    if isinstance(value, dict):
        if not value:
            return "[]"
        items = [
            f"{next_space}{to_php(str(key), indent + 1)} => {to_php(val, indent + 1)},"
            for key, val in value.items()
        ]
        return "[\n" + "\n".join(items) + f"\n{space}]"
    raise TypeError(type(value))

php_content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " + to_php(data) + ";\n"
php_path.write_text(php_content, encoding="utf-8")
print(php_path)
