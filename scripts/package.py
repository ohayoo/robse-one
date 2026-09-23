#!/usr/bin/env python3
"""Create the monorepo archive and installable WordPress ZIPs."""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT.parent

PACKAGES = [
    ("ROBSE-ONE-MVP.zip", ROOT, "robse-one"),
    ("ROBSE-ONE-Theme.zip", ROOT / "themes" / "robse-one", "robse-one"),
    ("ROBSE-ONE-Blocks.zip", ROOT / "plugins" / "robse-one-blocks", "robse-one-blocks"),
    ("ROBSE-ONE-AI.zip", ROOT / "plugins" / "robse-one-ai", "robse-one-ai"),
]

for archive, source, package_root in PACKAGES:
    destination = OUT / archive
    if destination.exists():
        destination.unlink()
    with ZipFile(destination, "w", ZIP_DEFLATED) as bundle:
        for path in sorted(source.rglob("*")):
            if path.is_file():
                bundle.write(path, Path(package_root) / path.relative_to(source))
    print(f"Created {destination}")
