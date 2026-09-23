#!/usr/bin/env python3
"""Static syntax and package preflight checks for ROBSE ONE."""
from pathlib import Path
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
errors = []

for path in sorted(ROOT.rglob("*.json")):
    try:
        json.loads(path.read_text(encoding="utf-8"))
        print("JSON OK:", path.relative_to(ROOT))
    except Exception as exc:
        errors.append(f"Invalid JSON {path.relative_to(ROOT)}: {exc}")

for path in sorted(ROOT.rglob("*.php")):
    result = subprocess.run(["php", "-l", str(path)], capture_output=True, text=True)
    if result.returncode:
        errors.append(f"PHP syntax error {path.relative_to(ROOT)}: {result.stdout}{result.stderr}")
    else:
        print("PHP OK:", path.relative_to(ROOT))

for path in sorted(ROOT.rglob("*.js")):
    result = subprocess.run(["node", "--check", str(path)], capture_output=True, text=True)
    if result.returncode:
        errors.append(f"JavaScript syntax error {path.relative_to(ROOT)}: {result.stdout}{result.stderr}")
    else:
        print("JavaScript OK:", path.relative_to(ROOT))

required = [
    "themes/robse-one/theme.json",
    "themes/robse-one/templates/index.html",
    "themes/robse-one/parts/header.html",
    "themes/robse-one/parts/footer.html",
    "plugins/robse-one-blocks/robse-one-blocks.php",
    "plugins/robse-one-ai/robse-one-ai.php",
]
for item in required:
    if not (ROOT / item).is_file():
        errors.append(f"Missing required file: {item}")

if errors:
    print("\n".join(errors), file=sys.stderr)
    raise SystemExit(1)
print("Static preflight passed.")
