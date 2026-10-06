#!/usr/bin/env python3
"""Create the monorepo archive and installable WordPress ZIPs."""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile
import re

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT.parent / "dist"
OUT.mkdir(exist_ok=True)

def version(path):
    match = re.search(r"^[ \t*]*Version:\s*(\S+)", path.read_text(), re.MULTILINE)
    if not match:
        raise ValueError(f"Missing version header: {path}")
    return match.group(1)


theme_version = version(ROOT / "themes" / "robse-one" / "style.css")
PACKAGES = [(f"robse-one-source-{theme_version}.zip", ROOT, "robse-one-source")]
for folder, slug, entry in [
    ("themes", "robse-one", "style.css"),
    ("plugins", "robse-one-blocks", "robse-one-blocks.php"),
    ("plugins", "robse-one-ai", "robse-one-ai.php"),
]:
    source = ROOT / folder / slug
    PACKAGES.append((f"{slug}-{version(source / entry)}.zip", source, slug))

for archive, source, package_root in PACKAGES:
    destination = OUT / archive
    if destination.exists():
        destination.unlink()
    with ZipFile(destination, "w", ZIP_DEFLATED) as bundle:
        for path in sorted(source.rglob("*")):
            if path.is_file() and not set(path.relative_to(source).parts).intersection({".git", "__pycache__", "node_modules"}) and path.name != ".DS_Store":
                bundle.write(path, Path(package_root) / path.relative_to(source))
    print(f"Created {destination}")
