"""Build a WordPress-installable ZIP from an explicit runtime allowlist."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo

root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('--sync-qa', action='store_true')
args = parser.parse_args()
version = re.search(r' \* Version: ([\d.]+)', (root / 'native-formsubmit-for-bricks.php').read_text()).group(1)
files = [root / name for name in ['native-formsubmit-for-bricks.php', 'uninstall.php', 'readme.txt', 'LICENSE']]
files += sorted((root / 'includes').glob('*.php'))
files += sorted((root / 'languages').glob('*.pot'))
build = root / 'build'
build.mkdir(exist_ok=True)
destination = build / f'native-formsubmit-for-bricks-{version}.zip'
entries = []
with ZipFile(destination, 'w', ZIP_DEFLATED, compresslevel=9) as archive:
    for source in files:
        data = source.read_bytes()
        assert not data.startswith(b'\xef\xbb\xbf'), f'BOM: {source}'
        assert b'\r' not in data, f'Non-LF line endings: {source}'
        data.decode('utf-8')
        relative = source.relative_to(root).as_posix()
        info = ZipInfo('native-formsubmit-for-bricks/' + relative, date_time=(2026, 1, 1, 0, 0, 0))
        info.compress_type = ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        archive.writestr(info, data, compresslevel=9)
        entries.append({'path': relative, 'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest()})
        if args.sync_qa:
            target = root / '.tools/wordpress/wp-content/plugins/native-formsubmit-for-bricks' / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(source, target)
with ZipFile(destination) as archive:
    assert archive.testzip() is None
    assert len(archive.namelist()) == len(files)
manifest = {'version': version, 'zip': destination.name, 'bytes': destination.stat().st_size,
            'sha256': hashlib.sha256(destination.read_bytes()).hexdigest(), 'files': entries}
(build / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
print(json.dumps({k: v for k, v in manifest.items() if k != 'files'}))
