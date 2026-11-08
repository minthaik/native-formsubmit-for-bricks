"""Validate the built archive, metadata, encoding and public tracked-file hygiene."""
import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import subprocess
from zipfile import ZipFile

root = Path(__file__).resolve().parents[1]
slug = 'native-formsubmit-for-bricks'
manifest = json.loads((root / 'build/manifest.json').read_text(encoding='utf-8'))
archive = root / 'build' / manifest['zip']
assert hashlib.sha256(archive.read_bytes()).hexdigest() == manifest['sha256'], 'ZIP hash mismatch'
expected = {slug + '/' + item['path']: item for item in manifest['files']}
with ZipFile(archive) as zipped:
    assert zipped.testzip() is None
    assert len(zipped.namelist()) == len(expected) == 15, 'Unexpected runtime file count'
    assert set(zipped.namelist()) == set(expected), 'Unexpected archive member'
    for name in zipped.namelist():
        parts = PurePosixPath(name).parts
        assert parts[0] == slug and '..' not in parts and '\\' not in name
        data = zipped.read(name)
        assert hashlib.sha256(data).hexdigest() == expected[name]['sha256']
        assert not data.startswith(b'\xef\xbb\xbf') and b'\r' not in data, name
        data.decode('utf-8')
        assert data == (root / expected[name]['path']).read_bytes(), 'Stale package: ' + name
header = (root / (slug + '.php')).read_text(encoding='utf-8')
readme = (root / 'readme.txt').read_text(encoding='utf-8')
version = manifest['version']
assert f' * Version: {version}\n' in header and f'Stable tag: {version}\n' in readme
assert f' * Text Domain: {slug}\n' in header
assert 'Plugin Name: Native FormSubmit for Bricks' in header
assert re.search(r'^Contributors: [a-zA-Z0-9_, -]+$', readme, re.M), 'Contributor username missing'
assert len(readme.encode('utf-8')) <= 16000, 'Readme unexpectedly large'
for required in ['== Installation ==', '== External service and privacy ==', 'https://formsubmit.co/privacy.pdf', '== Changelog ==']:
    assert required in readme, required

# Inspect only public tracked files. Private QA may exist locally, never in Git.
tracked = subprocess.run(['git', 'ls-files', '-z'], cwd=root, capture_output=True, check=True).stdout.decode().split('\0')
blocked = ('.tools/', '.private/', 'qa/', 'vendor/', 'node_modules/', 'build/')
secrets = re.compile(r'(?:gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,}|-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----|formsubmit\.co/confirm/[a-f0-9]{32})')
for name in filter(None, tracked):
    assert not name.startswith(blocked), 'Private/tooling file tracked: ' + name
    assert name not in ('AGENTS.md', 'docs/EXECUTION-STATE.md', 'scripts/live-attachment-qa.mjs')
    assert not Path(name).name.startswith('.env'), 'Environment file tracked'
    data = (root / name).read_bytes()
    if Path(name).suffix in ('.png', '.pdf'):
        continue
    text = data.decode('utf-8')
    assert not data.startswith(b'\xef\xbb\xbf') and b'\r' not in data, name
    assert not secrets.search(text), 'Potential secret in ' + name
    assert not re.search(r'[CD]:[/\\]Users[/\\]', text, re.I), 'Personal machine path in ' + name
print(json.dumps({'version': version, 'runtime_files': len(expected), 'tracked_files_checked': len(list(filter(None, tracked))), 'sha256': manifest['sha256'], 'status': 'passed'}))
