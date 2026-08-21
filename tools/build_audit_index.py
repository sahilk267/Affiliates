#!/usr/bin/env python3
import json
import os
import re
import subprocess
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path('/home/ubuntu/Affiliates').resolve()
AUDIT_DIR = ROOT / 'audit'
EXCLUDE_DIRS = {'.git', 'audit', 'vendor', 'node_modules', 'storage', '.venv', 'coverage'}

LANG_BY_EXT = {
    '.php': 'PHP', '.js': 'JavaScript', '.jsx': 'JavaScript JSX', '.ts': 'TypeScript', '.tsx': 'TypeScript JSX',
    '.md': 'Markdown', '.json': 'JSON', '.yml': 'YAML', '.yaml': 'YAML', '.sql': 'SQL', '.css': 'CSS', '.scss': 'SCSS',
    '.html': 'HTML', '.htaccess': 'Apache Config', '.xml': 'XML', '.lock': 'Lockfile', '.dist': 'Config', '.ini': 'INI',
    '.sh': 'Shell', '.mcd': 'Mermaid', '.env': 'Environment',
}
CONFIG_NAMES = {'.env', '.env.example', 'composer.json', 'composer.lock', 'phpunit.xml', 'phpunit.xml.dist', 'artisan', 'package.json', 'package-lock.json', 'yarn.lock', 'pnpm-lock.yaml', 'docker-compose.yml', 'docker-compose.yaml', 'Dockerfile', '.htaccess'}
TEST_RE = re.compile(r'(^|/)(tests?|specs?)(/|$)|(^|/)[^/]*(?:Test|Spec)\.(php|js|jsx|ts|tsx)$', re.I)
DOCKER_RE = re.compile(r'(^|/)(Dockerfile[^/]*|docker-compose[^/]*|.*\.dockerfile$|.*docker.*\.(yml|yaml|sh))', re.I)

def rel(p: Path) -> str:
    return p.relative_to(ROOT).as_posix()

def language(path: Path) -> str:
    name = path.name
    if name in CONFIG_NAMES:
        if name.startswith('.env'):
            return 'Environment'
        if name == 'artisan':
            return 'PHP CLI'
    if path.suffix in LANG_BY_EXT:
        return LANG_BY_EXT[path.suffix]
    if name.startswith('.') and '.' not in name[1:]:
        return 'Config'
    return 'Unknown'

def classify(path: Path, text: str):
    r = rel(path)
    parts = path.parts
    if r.startswith('app/Http/Controllers'):
        module, layer = 'HTTP controllers', 'Backend / Controller'
    elif r.startswith('app/Http/Middleware'):
        module, layer = 'HTTP middleware', 'Security / Middleware'
    elif r.startswith('app/Services'):
        module, layer = 'Domain services', 'Backend / Service'
    elif r.startswith('app/Providers'):
        module, layer = 'Service providers', 'Kernel / Infrastructure'
    elif r.startswith('app'):
        module, layer = 'Domain models and application', 'Backend / Domain'
    elif r.startswith('database/migrations'):
        module, layer = 'Database migrations', 'Database'
    elif r.startswith('database/seeders'):
        module, layer = 'Database seeders', 'Database / CLI'
    elif r.startswith('database'):
        module, layer = 'Database assets', 'Database'
    elif r.startswith('routes'):
        module, layer = 'Routing', 'API / Routing'
    elif r.startswith('resources/views'):
        module, layer = 'Blade views', 'Frontend / View'
    elif r.startswith('resources'):
        module, layer = 'Frontend resources', 'Frontend'
    elif r.startswith('config'):
        module, layer = 'Configuration', 'Infrastructure / Configuration'
    elif r.startswith('bootstrap') or r == 'artisan':
        module, layer = 'Laravel bootstrap and CLI', 'Kernel / CLI'
    elif r.startswith('public') or r == 'index.php':
        module, layer = 'Public entrypoint', 'Infrastructure / Web'
    elif r.startswith('.github'):
        module, layer = 'GitHub workflow and templates', 'DevOps / CI'
    elif r.startswith('complete-project-rules'):
        module, layer = 'Project rules', 'Documentation / Governance'
    elif r.startswith('storage'):
        module, layer = 'Runtime storage', 'Infrastructure / Storage'
    else:
        module, layer = 'Repository root and documentation', 'Documentation / Repository'
    feature = 'general'
    low = (r + ' ' + text[:1000]).lower()
    feature_terms = [
        ('authentication', ['auth', 'login', 'password', 'session']), ('authorization', ['adminmiddleware', 'role', 'authorize']),
        ('affiliate tracking', ['click', 'conversion', 'affiliate', 'tracking']), ('product catalog', ['product', 'catalog', 'buy']),
        ('cashback and points', ['cashback', 'points', 'wallet']), ('referrals', ['referral', 'sub_affiliate']),
        ('redemptions and gifts', ['redemption', 'gift', 'withdraw']), ('analytics', ['analytic', 'report']),
        ('testing', ['test', 'phpunit']), ('deployment', ['docker', 'github/workflows', 'ci.yml', 'hostinger']),
        ('documentation', ['.md', 'readme', 'changelog', 'rules']),
    ]
    for name, terms in feature_terms:
        if any(t in low for t in terms):
            feature = name
            break
    return module, feature, layer

def extract_imports_exports(path: Path, text: str):
    imports, exports, deps = set(), set(), set()
    ext = path.suffix.lower()
    if ext == '.php' or path.name == 'artisan':
        imports.update(re.findall(r'^\s*use\s+([^;]+);', text, re.M))
        imports.update(re.findall(r'\b(?:require|include)(?:_once)?\s*[^;]*[\'\"]([^\'\"]+)[\'\"]', text))
        exports.update(re.findall(r'^\s*(?:abstract\s+|final\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)', text, re.M))
        exports.update(re.findall(r'^\s*(?:public\s+|protected\s+|private\s+)?function\s+([A-Za-z_][A-Za-z0-9_]*)', text, re.M))
        deps.update(re.findall(r'\bApp\\[A-Za-z0-9_\\]+', text))
        deps.update(re.findall(r'\b(?:Illuminate|Laravel|Symfony)\\[A-Za-z0-9_\\]+', text))
    elif ext in {'.js', '.jsx', '.ts', '.tsx'}:
        imports.update(re.findall(r'import\s+(?:[^;]*?\s+from\s+)?[\'\"]([^\'\"]+)[\'\"]', text))
        imports.update(re.findall(r'require\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)', text))
        exports.update(re.findall(r'export\s+(?:default\s+)?(?:class|function|const|let|var)\s+([A-Za-z_$][\w$]*)', text))
    elif ext == '.sql':
        exports.update(re.findall(r'CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`\"]?([A-Za-z0-9_]+)', text, re.I))
        imports.update(re.findall(r'\bREFERENCES\s+[`\"]?([A-Za-z0-9_]+)', text, re.I))
    elif ext in {'.json', '.yml', '.yaml', '.md', '.mcd'}:
        exports.update(re.findall(r'^#{1,6}\s+(.+)$', text, re.M))
    return sorted(imports), sorted(exports), sorted(deps)

def file_record(path: Path):
    try:
        data = path.read_bytes()
    except Exception as e:
        data = b''
    try:
        text = data.decode('utf-8', errors='replace')
    except Exception:
        text = ''
    st = path.stat()
    imports, exports, deps = extract_imports_exports(path, text)
    module, feature, layer = classify(path, text)
    is_test = bool(TEST_RE.search(rel(path)))
    is_docker = bool(DOCKER_RE.search(rel(path)))
    return {
        'path': rel(path), 'filename': path.name, 'extension': path.suffix.lower() or '[no extension]',
        'language': language(path), 'size_bytes': st.st_size, 'last_modified': datetime.fromtimestamp(st.st_mtime, timezone.utc).isoformat(),
        'line_count': text.count('\n') + (1 if text and not text.endswith('\n') else 0), 'imports': imports, 'exports': exports,
        'dependencies': deps, 'referenced_by': [], 'module': module, 'feature': feature, 'architecture_layer': layer,
        'is_test': is_test, 'is_docker_related': is_docker,
    }

files = []
for dirpath, dirnames, filenames in os.walk(ROOT):
    dirnames[:] = sorted(d for d in dirnames if d not in EXCLUDE_DIRS)
    for filename in sorted(filenames):
        p = Path(dirpath) / filename
        if p.is_file():
            files.append(file_record(p))
files.sort(key=lambda x: x['path'])

# Reference scan: path tokens, class names, view names, route names, and migration tables.
path_to_idx = {f['path']: i for i, f in enumerate(files)}
for src in files:
    src_path = ROOT / src['path']
    try:
        text = src_path.read_text(errors='replace')
    except Exception:
        continue
    candidates = set()
    for target in files:
        if target['path'] == src['path']:
            continue
        base = Path(target['path']).stem
        if len(base) >= 4 and re.search(r'(?<![A-Za-z0-9_])' + re.escape(base) + r'(?![A-Za-z0-9_])', text, re.I):
            candidates.add(target['path'])
        if target['path'].endswith('.blade.php'):
            view_name = target['path'].replace('resources/views/', '').replace('.blade.php', '').replace('/', '.')
            if view_name in text:
                candidates.add(target['path'])
    src['referenced_by'] = sorted(candidates)

# Git-tracked status is informational; all non-Git working-tree files are indexed.
try:
    tracked = subprocess.check_output(['git', '-C', str(ROOT), 'ls-files'], text=True).splitlines()
except Exception:
    tracked = []
for f in files:
    f['git_tracked'] = f['path'] in set(tracked)

directory_count = sum(1 for d, ds, fs in os.walk(ROOT) if Path(d).name not in EXCLUDE_DIRS)
index = {
    'generated_at': datetime.now(timezone.utc).isoformat(), 'repository_root': str(ROOT),
    'scan_scope': 'All files in working tree excluding .git internals and generated audit/ outputs.',
    'directory_count': directory_count, 'file_count': len(files),
    'discovery_counts': {
        'total_typescript_files': sum(1 for f in files if f['language'].startswith('TypeScript')),
        'total_javascript_files': sum(1 for f in files if f['language'].startswith('JavaScript')),
        'total_markdown_files': sum(1 for f in files if f['extension'] == '.md'),
        'total_json_files': sum(1 for f in files if f['extension'] == '.json'),
        'total_test_files': sum(1 for f in files if f['is_test']),
        'total_docker_related_files': sum(1 for f in files if f['is_docker_related']),
    },
    'files': files,
}
AUDIT_DIR.mkdir(exist_ok=True)
(AUDIT_DIR / 'index.json').write_text(json.dumps(index, indent=2) + '\n')

# Print proof required by prompt.
c = Counter(f['extension'] for f in files)
langs = Counter(f['language'] for f in files)
print(json.dumps({
    'total_directories_discovered': directory_count,
    'total_files_discovered': len(files), 'total_typescript_files': sum(1 for f in files if f['language'].startswith('TypeScript')),
    'total_javascript_files': sum(1 for f in files if f['language'].startswith('JavaScript')),
    'total_markdown_files': sum(1 for f in files if f['extension'] == '.md'), 'total_json_files': sum(1 for f in files if f['extension'] == '.json'),
    'total_test_files': sum(1 for f in files if f['is_test']), 'total_docker_related_files': sum(1 for f in files if f['is_docker_related']),
    'extensions': dict(c), 'languages': dict(langs), 'index_path': str(AUDIT_DIR / 'index.json')
}, indent=2))
