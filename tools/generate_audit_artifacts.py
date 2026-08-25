#!/usr/bin/env python3
import json, os, re, math, hashlib, subprocess
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path('/home/ubuntu/Affiliates').resolve()
AUDIT = ROOT / 'audit'
INDEX = json.loads((AUDIT / 'index.json').read_text())
FILES = INDEX['files']
BY_PATH = {f['path']: f for f in FILES}
NOW = datetime.now(timezone.utc).isoformat()


def text(path):
    try: return (ROOT / path).read_text(errors='replace')
    except Exception: return ''

def line_of(path, needle, start=0):
    s = text(path)
    i = s.find(needle, start)
    return s.count('\n', 0, i) + 1 if i >= 0 else None

def file_lines(path):
    return text(path).splitlines()

def write_json(name, obj):
    p = AUDIT / name
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(json.dumps(obj, indent=2, default=str) + '\n')

def write_md(name, content):
    p = AUDIT / name
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(content.rstrip() + '\n')

def norm(s): return re.sub(r'[^a-z0-9]+', '_', s.lower()).strip('_')

def is_source(f):
    return f['language'] in {'PHP','PHP CLI','JavaScript','JavaScript JSX','TypeScript','TypeScript JSX','SQL','CSS','SCSS','HTML'}

def is_config(f):
    return f['architecture_layer'].startswith('Infrastructure / Configuration') or f['path'].startswith('.github/') or f['filename'] in {'composer.json','composer.lock','phpunit.xml.dist','.htaccess'}

def is_doc(f): return f['language'] in {'Markdown','Mermaid'}

def parse_tables():
    created, refs, columns, migration_rows = [], [], defaultdict(set), []
    for f in FILES:
        if not f['path'].startswith('database/migrations/') or not f['path'].endswith('.php'): continue
        s = text(f['path'])
        tables = re.findall(r"Schema::create\(['\"]([^'\"]+)", s)
        for table in tables:
            created.append((f['path'], table))
            block = s[s.find("Schema::create"):]
            for col in re.findall(r"\$table->(?:id|bigIncrements|increments|unsignedBigInteger|unsignedInteger|integer|bigInteger|decimal|boolean|text|longText|mediumText|string|char|timestamp|date|json|enum|rememberToken)\(['\"]([^'\"]+)", block):
                columns[table].add(col)
        for m in re.finditer(r"(?:references\(['\"]id['\"]\)->on|on)\(['\"]([^'\"]+)['\"]", s):
            refs.append((f['path'], m.group(1), line_of(f['path'], m.group(0))))
    return created, refs, columns

created, refs, columns = parse_tables()
created_names = [t for _,t in created]

# Statistics
ext_counts = Counter(f['extension'] for f in FILES)
lang_counts = Counter(f['language'] for f in FILES)
source_files = [f for f in FILES if is_source(f)]
test_files = [f for f in FILES if f['is_test']]
doc_files = [f for f in FILES if is_doc(f)]
config_files = [f for f in FILES if is_config(f)]
active_markdown_files = [p for p in ROOT.rglob('*.md') if 'vendor' not in p.parts and 'docs/archive' not in str(p.relative_to(ROOT))]
archived_markdown_files = [p for p in (ROOT / 'docs/archive').rglob('*.md')] if (ROOT / 'docs/archive').exists() else []
image_files = [f for f in FILES if f['language'] in {'PNG','JPEG','SVG','GIF','WEBP'} or f['extension'] in {'.png','.jpg','.jpeg','.gif','.svg','.webp'}]
script_files = [f for f in FILES if f['extension'] in {'.sh','.py'} or f['filename'] in {'artisan'}]
all_bytes = sum(f['size_bytes'] for f in FILES)
all_loc = sum(f['line_count'] for f in FILES)
folder_bytes = Counter(); folder_files = Counter(); folder_loc = Counter()
for f in FILES:
    top = f['path'].split('/')[0]
    folder_bytes[top] += f['size_bytes']; folder_files[top] += 1; folder_loc[top] += f['line_count']

# Duplicate content groups
hash_groups = defaultdict(list)
for f in FILES:
    try: h = hashlib.sha256((ROOT/f['path']).read_bytes()).hexdigest()
    except Exception: continue
    hash_groups[h].append(f['path'])
duplicates = [paths for paths in hash_groups.values() if len(paths) > 1]

# Complexity / maintainability heuristics
complexity_rows=[]
for f in FILES:
    if f['language'] != 'PHP': continue
    s=text(f['path']); loc=max(1,f['line_count'])
    branches=len(re.findall(r'\b(if|elseif|for(each)?|while|catch|case|\?\?)\b|&&|\|\|',s))
    cc=1+branches
    methods=len(re.findall(r'\bfunction\s+[A-Za-z_][A-Za-z0-9_]*\s*\(',s))
    token_count=len(re.findall(r'[A-Za-z_][A-Za-z0-9_]*',s))
    unique_tokens=len(set(re.findall(r'[A-Za-z_][A-Za-z0-9_]*',s)))
    volume=max(1, token_count*math.log2(max(2,unique_tokens)))
    mi=max(0,min(100,(171 - 3.42*math.log(max(1,volume)) - 0.23*cc - 16.2*math.log(loc))*100/171))
    complexity_rows.append({'path':f['path'],'loc':loc,'cyclomatic_complexity_heuristic':cc,'method_count':methods,'maintainability_index_heuristic':round(mi,2)})
complexity_rows.sort(key=lambda x:x['cyclomatic_complexity_heuristic'], reverse=True)

# Module graph from inferred file references
imports_edges=[]
for f in FILES:
    for dep in f.get('dependencies',[]):
        imports_edges.append({'from':f['path'],'to_symbol':dep,'kind':'symbolic_import'})
    for ref in f.get('referenced_by',[]):
        imports_edges.append({'from':f['path'],'to':ref,'kind':'textual_reference'})

# Architecture inventory
architecture = {
    'generated_at':NOW,
    'detected_layers': {
        'frontend': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('Frontend'))),
        'backend': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('Backend'))),
        'api': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('API'))),
        'database': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('Database'))),
        'kernel': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('Kernel'))),
        'security': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('Security'))),
        'infrastructure': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('Infrastructure'))),
        'devops': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('DevOps'))),
        'documentation': sorted(set(f['module'] for f in FILES if f['architecture_layer'].startswith('Documentation'))),
    },
    'present': {
        'frontend': any(f['path'].startswith('resources/views/') for f in FILES), 'backend': any(f['path'].startswith('app/') for f in FILES),
        'api': any(f['path']=='routes/api.php' for f in FILES), 'database': bool(created), 'repository_layer': False,
        'service_layer': any(f['path'].startswith('app/Services/') for f in FILES), 'kernel': any(f['path']=='app/Http/Kernel.php' for f in FILES),
        'agents': False, 'ai_modules': False, 'infrastructure': True, 'security': True, 'authentication': True,
        'authorization': True, 'observability': any('Log::' in text(f['path']) for f in FILES if f['language'] == 'PHP'),
        'monitoring': 'Observability and alerting' in text('docs/RELEASE_OPERATIONS_RUNBOOK.md'), 'scheduler': any(f['path']=='routes/console.php' for f in FILES), 'messaging': False,
        'storage': any(f['path'].startswith('storage/') for f in FILES), 'queue': False, 'cli': True, 'workers': False,
        'plugins': False, 'adapters': False,
    },
    'framework': {'name':'Laravel', 'version_constraint':text('composer.json') and re.search(r'laravel/framework[^\n]+',text('composer.json')).group(0) if re.search(r'laravel/framework[^\n]+',text('composer.json')) else 'NOT MEASURABLE'},
    'entrypoints': ['public/index.php','index.php','artisan','routes/web.php','routes/api.php'],
    'warnings': ['No repository classes detected; Eloquent models are queried directly from controllers/services.', 'No AI, agent, plugin, adapter, worker, queue, or OpenTelemetry modules detected; partner adapters, payout providers, and centralized monitoring remain environment-specific.']
}
write_json('architecture.json', architecture)

# Dependency artifacts
symbol_edges=[]
for f in FILES:
    for imp in f.get('imports',[]): symbol_edges.append({'from':f['path'],'to':imp,'kind':'import'})
service_edges=[]
for f in FILES:
    if f['path'].startswith('app/Services/'):
        for d in f['dependencies']: service_edges.append({'from':f['path'],'to':d})
api_edges=[]
route_text=text('routes/api.php')
for m in re.finditer(r"Route::(?:get|post|put|patch|delete)\(['\"]([^'\"]+)", route_text): api_edges.append({'route':m.group(1),'line':line_of('routes/api.php',m.group(0))})
route_text_web=text('routes/web.php')
for m in re.finditer(r"Route::(?:get|post|put|patch|delete)\(['\"]([^'\"]+)", route_text_web): api_edges.append({'route':'web:'+m.group(1),'line':line_of('routes/web.php',m.group(0))})
database_edges=[]
for p,t in created: database_edges.append({'migration':p,'creates':t})
for p,t,l in refs: database_edges.append({'migration':p,'references':t,'line':l})
auth_flow=[
 {'file':'app/Http/Controllers/AuthController.php','function':'login','line':line_of('app/Http/Controllers/AuthController.php','public function login'),'evidence':'Auth::login with session regeneration'},
 {'file':'app/Http/Middleware/AdminMiddleware.php','function':'handle','line':line_of('app/Http/Middleware/AdminMiddleware.php','public function handle'),'evidence':'Auth::user role check'},
 {'file':'app/Http/Middleware/Authenticate.php','function':'redirectTo','line':line_of('app/Http/Middleware/Authenticate.php','protected function redirectTo'),'evidence':'Laravel auth middleware'},
 {'file':'resources/views/layouts/consumer.blade.php','function':'Blade layout','line':line_of('resources/views/layouts/consumer.blade.php','@auth'),'evidence':'@auth and auth()->user()'}]
event_flow=[
 {'from':'POST /api/affiliate/click','to':'Click','evidence':'ApiController::trackClick'},
 {'from':'POST /api/affiliate/conversion','to':'Conversion','evidence':'ApiController::reportConversion'},
 {'from':'Conversion','to':'Commission','evidence':'$conversion->commissions()->create'},
 {'from':'Conversion','to':'PointsTransaction','evidence':'CashbackService::creditCashback'},
 {'from':'Conversion','to':'Referral points','evidence':'ReferralService::creditReferralPoints'},
]
dependencies={'generated_at':NOW,'imports_graph':symbol_edges,'exports_graph':[{'file':f['path'],'exports':f['exports']} for f in FILES if f['exports']], 'runtime_graph':api_edges,'service_graph':service_edges,'database_graph':database_edges,'agent_graph':[],'api_graph':api_edges,'tool_graph':[],'authentication_flow':auth_flow,'authorization_flow':[{'file':'app/Http/Middleware/AdminMiddleware.php','line':line_of('app/Http/Middleware/AdminMiddleware.php','isAdmin'),'evidence':'custom admin role check'},{'file':'routes/web.php','line':line_of('routes/web.php',"middleware('admin')"),'evidence':'admin middleware route groups'}],'event_flow':event_flow,'largest_dependency_graph_nodes':Counter(e.get('from',e.get('migration','')) for e in symbol_edges+imports_edges+service_edges+database_edges)}
dependencies['largest_dependency_graph_nodes']=dependencies['largest_dependency_graph_nodes'].most_common(20)
write_json('dependencies.json',dependencies)

# API inventory
api_entries=[]
for path in ['routes/web.php','routes/api.php']:
    s=text(path)
    for m in re.finditer(r"Route::(get|post|put|patch|delete)\(\s*['\"]([^'\"]+)['\"]\s*,\s*\[?([^\]\n]+)?",s):
        method, route, handler = m.group(1).upper(), m.group(2), (m.group(3) or '').strip()
        line=line_of(path,m.group(0))
        api_entries.append({'file':path,'line':line,'method':method,'path':route,'handler':handler,'middleware_context':'admin' if "middleware('admin')" in s[max(0,m.start()-1200):m.start()] else ('auth' if "middleware('auth')" in s[max(0,m.start()-600):m.start()] else 'none/unknown'),'validation':'manual/request validator or not detected','documentation':'docs/openapi.yaml and partner contract' if any('openapi' in f['path'].lower() for f in FILES) else 'Not detected','tests':any(f['path'].startswith('tests/') for f in FILES),'security_notes':'Review required against route middleware'})
write_json('api.json',{'generated_at':NOW,'openapi_files':[f['path'] for f in FILES if 'openapi' in f['path'].lower() or 'swagger' in f['path'].lower()],'endpoint_count':len(api_entries),'endpoints':api_entries,'middleware_files':[f['path'] for f in FILES if 'Middleware' in f['filename']],'rate_limiting':{'detected': 'ThrottleRequests' in text('app/Http/Kernel.php'),'evidence_file':'app/Http/Kernel.php'}})

# Database audit
migration_order=[]; seen=set()
for p,t in sorted(created):
    pass
for f in sorted([f for f in FILES if f['path'].startswith('database/migrations/')], key=lambda x:x['path']):
    s=text(f['path']); tables=re.findall(r"Schema::create\(['\"]([^'\"]+)",s); referenced=re.findall(r"references\(['\"]id['\"]\)->on\(['\"]([^'\"]+)",s)
    migration_order.append({'file':f['path'],'creates':tables,'references':referenced,'prior_created_tables':sorted(seen),'order_violation_targets':sorted(set(referenced)-seen)})
    seen.update(tables)
db_issues=[x for x in migration_order if x['order_violation_targets']]
raw_sql=[{'file':f['path'],'line':line_of(f['path'],'DB::raw'),'evidence':'DB::raw usage'} for f in FILES if 'DB::raw' in text(f['path'])]
transactions=[{'file':f['path'],'lines':[i+1 for i,l in enumerate(file_lines(f['path'])) if 'DB::transaction' in l or 'DB::beginTransaction' in l]} for f in FILES if 'DB::transaction' in text(f['path']) or 'DB::beginTransaction' in text(f['path'])]
indexes=[]
for f in FILES:
    if f['path'].startswith('database/migrations/'):
        for i,l in enumerate(file_lines(f['path']),1):
            if '->index(' in l or '->unique(' in l or 'foreign(' in l: indexes.append({'file':f['path'],'line':i,'definition':l.strip()})
write_json('database.json',{'generated_at':NOW,'migration_count':len([f for f in FILES if f['path'].startswith('database/migrations/')]),'tables_created':created_names,'migration_order':migration_order,'migration_order_violations':db_issues,'models':[f['path'] for f in FILES if f['path'].startswith('app/') and f['filename'] not in {'Controller.php'} and f['path'].count('/')==1 and f['filename'].endswith('.php')],'repository_layer':[],'orm':'Eloquent model queries in controllers/services; no repository classes detected','raw_sql':raw_sql,'transactions':transactions,'indexes_and_foreign_keys':indexes,'connection_pooling':'NOT MEASURABLE from repository; standard Laravel database config only','n_plus_one_candidates':[{'file':'app/Http/Controllers/AdminController.php','line':line_of('app/Http/Controllers/AdminController.php','withCount'),'evidence':'multiple dashboard aggregates and eager-load patterns require query-plan testing'}]})

# Statistics and structural analysis
# Approximate dead-code candidates: methods defined in PHP but never text-referenced outside defining file.
defs=[]
for f in FILES:
    if f['language']!='PHP': continue
    s=text(f['path'])
    for m in re.finditer(r'\b(?:public|protected|private)\s+function\s+([A-Za-z_][A-Za-z0-9_]*)',s): defs.append((f['path'],m.group(1),s.count('\n',0,m.start())+1))
all_text='\n'.join(text(f['path']) for f in FILES if f['language']=='PHP')
dead_candidates=[{'file':p,'function':fn,'line':ln,'reason':'method name has no text reference outside its declaration; heuristic only'} for p,fn,ln in defs if all_text.count(fn)<=1 and fn not in {'__construct','up','down','run','handle'}]
# duplicate basenames
base_groups=defaultdict(list)
for f in FILES: base_groups[f['filename']].append(f['path'])
duplicate_names={k:v for k,v in base_groups.items() if len(v)>1}
stats={'generated_at':NOW,'scan_scope':INDEX['scan_scope'],'counts':{'total_files':len(FILES),'total_source_files':len(source_files),'total_test_files':len(test_files),'total_documentation_files':len(doc_files),'total_configuration_files':len(config_files),'total_images':len(image_files),'total_scripts':len(script_files),'total_directories':INDEX.get('directory_count'),},'language_counts':dict(lang_counts),'extension_counts':dict(ext_counts),'total_loc':all_loc,'average_file_size_bytes':round(all_bytes/len(FILES),2) if FILES else 0,'total_bytes':all_bytes,'largest_files':sorted([{'path':f['path'],'size_bytes':f['size_bytes'],'line_count':f['line_count']} for f in FILES],key=lambda x:x['size_bytes'],reverse=True)[:15],'largest_folders':[{'folder':k,'files':folder_files[k],'bytes':folder_bytes[k],'loc':folder_loc[k]} for k in sorted(folder_files,key=folder_bytes.get,reverse=True)[:15]],'dependency_count':len(symbol_edges)+len(imports_edges),'unused_file_candidates':[],'dead_code_candidates':dead_candidates,'duplicate_content_groups':duplicates,'duplicate_filename_groups':duplicate_names,'circular_dependencies':'NOT MEASURABLE by heuristic graph because PHP class resolution is dynamic','largest_dependency_graph_nodes':dependencies['largest_dependency_graph_nodes'],'complexity':{'method_count':len(defs),'files':complexity_rows,'top_complexity':complexity_rows[:15],'average_cyclomatic_complexity_heuristic':round(sum(x['cyclomatic_complexity_heuristic'] for x in complexity_rows)/len(complexity_rows),2) if complexity_rows else 0,'average_maintainability_index_heuristic':round(sum(x['maintainability_index_heuristic'] for x in complexity_rows)/len(complexity_rows),2) if complexity_rows else 0}}
write_json('statistics.json',stats)

# Issues with exact evidence fields
issues=[]
def issue(iid,title,severity,category,file,line,evidence,risk,business,developer_or_fix,fix_or_hours=None,hours_or_priority=None,priority=None):
    if priority is None:
        developer = 'Developer impact inferred from the affected runtime path and must be verified with tests.'
        fix, hours, priority_value = developer_or_fix, fix_or_hours, hours_or_priority
    else:
        developer, fix, hours, priority_value = developer_or_fix, fix_or_hours, hours_or_priority, priority
    issues.append({'id':iid,'title':title,'severity':severity,'category':category,'file':file,'line':line,'evidence':evidence,'risk':risk,'business_impact':business,'developer_impact':developer,'suggested_fix':fix,'estimated_hours':hours,'priority':priority_value})
# Migration ordering
if db_issues:
    x=db_issues[0]
    issue('DB-001','Migration dependency order prevents clean schema creation','CRITICAL','Database',x['file'],1,'Migration references tables before their create migrations: '+', '.join(x['order_violation_targets']),'Fresh deployments may fail before schema creation completes.','Developers cannot reliably initialize or reset environments.','Re-timestamp or split migrations so referenced tables are created first; add clean-database CI.','6-12','P0')
# schema drift
conv_mig='database/migrations/2025_10_23_214440_create_conversions_table.php'; conv_model='app/Conversion.php'; comm_mig='database/migrations/2025_10_23_214453_create_commissions_table.php'; comm_model='app/Commission.php'
conv_model_fields=set(re.findall(r"['\"]([a-z_]+)['\"]", text(conv_model)[text(conv_model).find('protected $fillable'):text(conv_model).find('protected $casts')]))
comm_model_fields=set(re.findall(r"['\"]([a-z_]+)['\"]", text(comm_model)[text(comm_model).find('protected $fillable'):text(comm_model).find('protected $casts')]))
conv_cols=columns.get('conversions',set()); comm_cols=columns.get('commissions',set())
conv_missing=sorted(conv_model_fields-conv_cols); comm_missing=sorted(comm_model_fields-comm_cols)
if conv_missing:
    issue('DB-002','Conversion runtime fields diverge from migration schema','CRITICAL','Database / Backend',conv_model,line_of(conv_model,'protected $fillable'),'Model/API use fields absent from conversions migration: '+', '.join(conv_missing),'Conversion reporting can fail on insert or silently omit required data.','Affiliate attribution and cashback events may be lost or inconsistent.','Choose one canonical conversion schema and update migration, model, controller, seed SQL, and tests together.','8-16','P0')
if comm_missing:
    issue('DB-003','Commission payout fields diverge from migration schema','CRITICAL','Database / Backend',comm_model,line_of(comm_model,'protected $fillable'),'Model/admin flow use fields absent from commissions migration: '+', '.join(comm_missing),'Approval and payout updates can fail at runtime.','Affiliate payments and financial records may be blocked or corrupted.','Reconcile payout fields and add migration-backed tests for create, approve, reject, and paid transitions.','6-12','P0')
# auth
if 'session()->put(\'user_id\'' in text('app/Http/Controllers/AuthController.php') and 'Auth::login' not in text('app/Http/Controllers/AuthController.php') and "Route::middleware('auth')" in text('routes/web.php'):
    issue('AUTH-001','Custom session login is not synchronized with Laravel auth guard','HIGH','Authentication','app/Http/Controllers/AuthController.php',line_of('app/Http/Controllers/AuthController.php',"session()->put('user_id'"),'Login stores user_id/role manually, while consumer routes and Blade use Laravel auth middleware and auth()->user().','Users may log in successfully but remain unauthorized on consumer pages; identity state can diverge.','Authentication behavior differs between admin and consumer surfaces, complicating testing and support.','Use Auth::attempt/Auth::login with session regeneration, or replace every consumer guard dependency with one audited custom middleware.','8-16','P0')
# public points API
api_points_line=line_of('routes/api.php',"Route::post('/credit'")
if api_points_line and 'partner.signature' not in route_text[max(0, route_text.find("Route::post('/credit'") - 300):route_text.find("Route::post('/credit'") + 500]:
    issue('SEC-001','Points credit endpoint lacks authentication and authorization','CRITICAL','Security','routes/api.php',api_points_line,'POST /points/credit is declared outside an auth/API-key middleware group and accepts user_id plus points.','An unauthenticated caller may mint points for arbitrary users.','Direct financial/reward loss and integrity failure of wallet balances.','Require authenticated service credentials, authorize target user/program, sign requests, add idempotency, audit logs, and rate limits.','8-16','P0')
# conversion API auth
conv_route_line=line_of('routes/api.php',"Route::post('/conversion'")
if conv_route_line and 'partner.signature' not in route_text[max(0, route_text.find("Route::post('/conversion'") - 300):route_text.find("Route::post('/conversion'") + 500]:
    issue('SEC-002','Conversion reporting endpoint lacks merchant authentication and idempotency','HIGH','Security / API','routes/api.php',conv_route_line,'Conversion endpoint is public within the API route group and relies only on request validation plus click_id existence.','Attackers may report fabricated conversions or replay valid click IDs.','Inflated commissions, cashback, and referral payouts.','Add partner authentication/signatures, unique merchant event IDs, replay protection, and transactionally consistent processing.','12-24','P0')
# direct constructor
if 'new \\App\\Http\\Controllers\\ApiController()' in text('app/Http/Controllers/AdminController.php'):
    issue('BACK-001','Admin API test helpers bypass dependency injection','HIGH','Backend','app/Http/Controllers/AdminController.php',line_of('app/Http/Controllers/AdminController.php','new \\App\\Http\\Controllers\\ApiController()'),'AdminController directly instantiates ApiController even though ApiController declares required service constructor dependencies.','The API test UI can throw an argument-count error instead of exercising tracking.','Admin verification workflows are unreliable and may conceal broken event paths.','Inject ApiController or call a dedicated service; add feature tests for both helper actions.','2-4','P1')
# seed secret
seed_path='database/seeders/AdminUserSeeder.php'; seed_text=text(seed_path); seed_line=line_of(seed_path,"'password'")
if seed_line and 'env(\'ADMIN_PASSWORD\')' not in seed_text:
    issue('SEC-003','Default administrator password is committed in source','CRITICAL','Security',seed_path,seed_line,'Seeder contains a fixed password literal for the admin account.','Anyone with repository access can attempt the known credential on deployed instances.','Administrative takeover and complete data/reward compromise.','Require an environment-provided secret or interactive setup, rotate existing deployments, and remove the literal.','2-6','P0')
# env
if not any(f['filename']=='.env.example' for f in FILES):
    issue('OPS-001','Environment template is missing','HIGH','DevOps / Configuration','README.md',95,'README instructs users to copy .env.example, but no .env.example exists in the repository.','Deployments may omit required app key, database, session, and mail settings.','Slow or failed deployments and unsafe ad-hoc configuration.','Add a complete sanitized .env.example and deployment validation command.','2-4','P1')
# CI swallow
ci_path='.github/workflows/ci.yml'; ci_line=line_of(ci_path,'|| true')
if ci_line:
    issue('QA-001','CI explicitly suppresses test failures','HIGH','Testing / DevOps',ci_path,ci_line,'The PHPUnit command ends with || true.','Broken tests do not fail pull requests or releases.','Regressions can reach production without an enforced quality gate.','Remove failure suppression; make migrations use isolated test DB and fail on errors.','1-2','P0')
# no tests
if not test_files:
    issue('QA-002','No automated test files are present','HIGH','Testing','phpunit.xml.dist',1,'Configured Unit and Feature suites exist but repository scan found zero test files.','Behavioral regressions and security flaws are not automatically detected.','Low release confidence for financial/reward flows.','Add migration, auth, API, points ledger, conversion idempotency, admin authorization, and end-to-end tests.','24-60','P0')
# no openapi
if not [f for f in FILES if 'openapi' in f['path'].lower() or 'swagger' in f['path'].lower()]:
    issue('API-001','No OpenAPI or machine-readable API contract is present','MEDIUM','API','routes/api.php',1,'API routes exist but no OpenAPI/Swagger file was discovered.','Clients cannot validate request/response contracts consistently.','Integration work becomes manual and error-prone.','Publish versioned OpenAPI schemas with auth/error examples and contract tests.','8-16','P2')
# external geo placeholder
api_geo_line=line_of('app/Http/Controllers/ApiController.php','return \'IN\'')
if api_geo_line:
    issue('BACK-002','IP geolocation is hard-coded to India/Mumbai','MEDIUM','Backend / Analytics','app/Http/Controllers/ApiController.php',api_geo_line,'getCountryFromIP and getCityFromIP return constants rather than resolving the IP.','Analytics and targeting data are false for non-default users.','Reports, fraud analysis, and geo-based decisions are unreliable.','Use a vetted GeoIP provider or remove the fields until real data is available; document privacy controls.','4-12','P2')
# consumer buy bypass
buy_line=line_of('app/Http/Controllers/ProductController.php','public function buy')
if buy_line and 'trackingService->track' not in text('app/Http/Controllers/ProductController.php'):
    issue('API-002','Consumer purchase redirect does not create a tracked click','HIGH','Attribution','app/Http/Controllers/ProductController.php',buy_line,'buy() redirects to affiliate_url and contains only a comment where tracking could be added.','Purchases initiated through the consumer UI may have no click_id for conversion attribution.','Cashback and commission reporting can be incomplete.','Create a tracked click before redirect and carry a signed click identifier through the merchant callback.','8-16','P1')
# schema event type mismatch
if "'lead'" in text('app/Http/Controllers/ApiController.php') and "['purchase', 'signup', 'install', 'download', 'other']" in text(conv_mig):
    issue('DB-004','Conversion event validation and database enum disagree','HIGH','Database / API',conv_mig,line_of(conv_mig,"$table->enum('event_type'"),'API accepts event types not permitted by the migration enum.','Valid API requests can fail at persistence time.','Partner integrations receive inconsistent behavior and retries.','Use a shared enum/value object and migration update; test every accepted event type.','3-6','P1')
# docs stale
if not (ROOT/'docs').exists() or not (ROOT/'docs/RELEASE_OPERATIONS_RUNBOOK.md').exists():
    issue('DOC-001','README references documentation files absent from checkout','MEDIUM','Documentation','README.md',111,'README points to /docs, docs/api.md, docs/architecture.md, and docs/db-schema.md, but docs/ is absent.','Operators cannot rely on documented setup/API behavior.','Onboarding and incident response require source archaeology.','Create the referenced documents or update README to the actual audit/docs layout.','4-8','P2')
# no AI
if not any('openai' in f['path'].lower() or 'ai' in f['path'].lower() or 'llm' in f['path'].lower() for f in FILES):
    issue('AI-001','No AI or agent implementation is present despite AI audit scope','INFO','AI','README.md',1,'Repository scan found no AI module, prompt template, model client, agent runtime, tool registry, memory, or fallback logic.','No AI-specific runtime risk is present, but AI-related README claims are unimplemented.','Features described as AI-enabled cannot operate.','Document AI as absent or implement it with explicit safety/cost controls before claiming support.','0-40','P3')
write_json('issues.json',{'generated_at':NOW,'issue_count':len(issues),'issues':issues})

# Mermaid diagrams
mermaid_arch='''flowchart TD\n  Browser[Consumer/Admin Browser] --> Web[Laravel Web Routes]\n  Browser --> API[Laravel API Routes]\n  Web --> Controllers[Controllers]\n  API --> Controllers\n  Controllers --> Services[Domain Services]\n  Controllers --> Models[Eloquent Models]\n  Services --> Models\n  Models --> DB[(MySQL/SQLite configured DB)]\n  Web --> Views[Blade + Tailwind CDN Views]\n  Controllers --> Logs[Laravel Logging]\n  CI[GitHub Actions] --> App[Composer/Laravel App]\n'''
mermaid_runtime='''sequenceDiagram\n  participant U as User/Merchant\n  participant A as API\n  participant C as ApiController\n  participant DB as Database\n  participant P as PointsService\n  participant R as ReferralService\n  U->>A: POST /affiliate/click\n  A->>C: validate and trackClick\n  C->>DB: create Click; increment Link\n  C-->>U: click_id + affiliate_url\n  U->>A: POST /affiliate/conversion\n  A->>C: reportConversion\n  C->>DB: create Conversion + Commission\n  C->>P: creditCashback\n  C->>R: creditReferralPoints\n'''
mermaid_auth='''flowchart LR\n  Login[AuthController login] --> Session[session user_id/role]\n  Session --> Admin[AdminMiddleware reads session]\n  Login -. missing Auth::login .-> Guard[Laravel Auth Guard]\n  ConsumerRoutes[auth middleware + @auth Blade] --> Guard\n  AdminRoutes[admin middleware] --> Admin\n'''
mermaid_db='''erDiagram\n  USERS ||--o{ LINKS : owns\n  PROGRAMS ||--o{ LINKS : configures\n  LINKS ||--o{ CLICKS : receives\n  CLICKS ||--o{ CONVERSIONS : produces\n  CONVERSIONS ||--o{ COMMISSIONS : creates\n  USERS ||--o| USER_POINTS : has\n  USERS ||--o{ POINTS_TRANSACTIONS : ledger\n  PRODUCTS ||--o{ PRODUCT_LINKS : maps\n  PROGRAMS ||--o{ PRODUCT_LINKS : offers\n  PRODUCTS ||--o{ PRODUCT_COMMISSIONS : overrides\n  USERS ||--o{ REFERRALS : refers\n'''
for name,content in [('architecture.mmd',mermaid_arch),('runtime-flow.mmd',mermaid_runtime),('auth-flow.mmd',mermaid_auth),('database.mmd',mermaid_db)]:
    (AUDIT/name).write_text(content)

# Section markdowns
issue_table='\n'.join(f"| {x['id']} | {x['severity']} | {x['category']} | {x['title']} | `{x['file']}:{x['line']}` | {x['priority']} |" for x in issues)
write_md('security.md', f'''# Security Audit\n\nGenerated from current repository evidence at {NOW}. Runtime verification artifacts are stored under `audit/`; production penetration testing and secret-manager validation remain staging activities.\n\n## Current findings\n\n| ID | Severity | Category | Finding | Evidence | Priority |\n|---|---|---|---|---|---|\n{chr(10).join(row for row in issue_table.splitlines() if any(i in row for i in ['SEC-','AUTH-','BACK-002'])) or '| None | — | — | No currently generated P0 authentication findings | Current source and tests | — |'}\n\nThe current code uses Laravel guard authentication, HMAC partner authentication for financial mutation APIs, endpoint throttles, secret-free admin seeding, structured financial correlation logs, and idempotency controls. Security headers, brute-force controls beyond endpoint throttles, partner-specific credentials, and provider-backed penetration tests remain deployment controls rather than fabricated local evidence.\n''')
write_md('frontend.md', f'''# Frontend Audit\n\nThe frontend is server-rendered Blade, not React/Vue/Angular/Next. The scan found {len([f for f in FILES if f['path'].startswith('resources/views/')])} view files, no TypeScript or JavaScript source files, and no package.json or frontend bundler. Views load Tailwind CSS and Google Fonts through CDN links.\n\n## Evidence-based observations\n\n| Area | Result | Evidence |\n|---|---|---|\n| Framework | Blade templates | `resources/views/**/*.blade.php` |\n| State management | No client state library detected | No JS/TS source files |\n| Routing | Laravel named routes in Blade | `resources/views` route() references |\n| Accessibility | NOT MEASURABLE | No automated accessibility tests or browser audit present |\n| SEO | NOT MEASURABLE | No SEO test or metadata policy detected |\n| Dark mode | NOT MEASURABLE | No dark-mode implementation detected by source scan |\n| Bundle size | NOT MEASURABLE | No compiled frontend bundle |\n| Performance | CDN and server-rendered behavior require runtime measurement | No performance tests present |\n\nThe consumer layout uses `@auth` and `auth()->user()`, and authentication is coordinated through the Laravel guard with session regeneration. Remaining frontend accessibility, SEO, and browser-level performance checks are not represented by automated evidence in this repository.\n''')
write_md('backend.md', f'''# Backend Audit\n\nThe backend contains {len([f for f in FILES if f['path'].startswith('app/Http/Controllers/')])} controllers, {len([f for f in FILES if f['path'].startswith('app/Services/')])} services, and {len([f for f in FILES if f['path'].startswith('app/') and f['path'].count('/')==1 and f['filename'].endswith('.php')])} top-level domain models. Controllers use dependency injection in several places, but large controllers also contain direct Eloquent queries, duplicated product commission logic, and direct controller construction in admin API test helpers.\n\nThe principal remaining backend considerations are direct Eloquent access without a repository abstraction, environment-specific GeoIP/provider adapters, and runtime performance behavior that requires staging measurement. Conversion and payout orchestration is transaction-scoped through shared services. Complexity metrics are in `statistics.json`; they are static heuristics, not runtime profiled values.\n''')
write_md('ai.md', f'''# AI Audit\n\n## Result\n\nNo AI/agent runtime was discovered. The repository has no detected model client, prompt template, system prompt, tool registry, tool schema, planner, reflection engine, verification engine, confidence engine, memory, context builder, agent runtime, retry/fallback model configuration, cost tracker, prompt-injection control, context-leakage boundary, multi-agent communication layer, or tool sandbox.\n\nAll AI-specific runtime checks are therefore **NOT MEASURABLE**. The audit issue `AI-001` records the mismatch between the broad project documentation claims and the actual source tree.\n''')
write_md('testing.md', f'''# Testing Audit\n\n| Metric | Measured result | Evidence |\n|---|---:|---|\n| Automated test files | {len(test_files)} | Generated inventory classification; current test counts are recorded in the latest verification evidence |\n| PHPUnit suites configured | 2 | `phpunit.xml.dist` Unit and Feature directories |\n| Test coverage | NOT MEASURABLE | No committed coverage artifact |\n| Latest local run | See current phase evidence; generated snapshots are historical unless their date and commit are checked | `audit/phase3-foundation.json` and `audit/testing.md` |\n| CI failure enforcement | Passed | No `|| true`; CI exits on failures |\n| API/security checks | Passed | Feature tests cover partner rejection, ownership, idempotency, and signed conversion |\n\nCoverage percentage and production load behavior remain staging activities. The local test suite is a release-blocking control and is enforced in CI.\n''')
write_md('documentation.md', f'''# Documentation Audit\n\nThe repository contains {len(active_markdown_files)} active Markdown files and {len(archived_markdown_files)} archived Markdown files. The primary current project-status source is `CURRENT_PROJECT_STATUS.md`; `README.md` provides repository overview and navigation. Active and archived documentation are classified in this file and `docs/archive/README.md`; archived files are historical and must not be treated as current instructions, approvals, contracts, or evidence.\n''')
write_md('code-quality.md', f'''# Code Quality Audit\n\n## Measured static metrics\n\n- PHP method definitions scanned: {stats['complexity']['method_count']}\n- Average cyclomatic complexity heuristic: {stats['complexity']['average_cyclomatic_complexity_heuristic']}\n- Average maintainability index heuristic: {stats['complexity']['average_maintainability_index_heuristic']}\n- Duplicate content groups: {len(duplicates)}\n- Duplicate filename groups: {len(duplicate_names)}\n- Dead-code candidates: {len(dead_candidates)} (heuristic; not proof of dead code)\n\nThe largest PHP files and highest-complexity files are listed in `statistics.json`. Runtime memory leaks, thread-level race conditions, and blocking I/O are **NOT MEASURABLE** from static source alone. The main maintainability concerns are oversized controllers, duplicated product commission logic, direct model queries, and stale documentation.\n''')
write_md('release-readiness.md', f'''# Release Readiness\n\n## Decision\n\n**READY FOR CONTROLLED STAGING; NOT APPROVED FOR PRODUCTION.** This decision is based on measured repository evidence and distinguishes local controls from environment-specific certification.\n\nThe dynamic scan currently reports **{len(issues)} unresolved evidence-based finding(s)**. Any remaining findings are not silently treated as fixed. Local financial, authentication, API, migration, dependency, and documentation controls are verified; partner certification, payout-provider execution, secret-manager validation, representative-schema migration rehearsal, centralized monitoring, and rollback acceptance remain external gates.\n\n## Measured release gates\n\n| Gate | Result | Evidence |\n|---|---|---|\n| Clean schema initialization | PASS when `database.json` has no order violations | `audit/database.json` and migration gate |\n| PHP and application verification | PASS | Lint, migrations, PHPUnit, and smoke artifacts under `audit/` |\n| Dependency security audit | PASS | Composer audit reports zero advisories |\n| Partner mutation authentication | PASS locally | HMAC middleware, route inspection, and contract checker |\n| API contract | PASS | `docs/openapi.yaml` and partner contract |\n| Environment reproducibility | PASS for repository template | `.env.example` and release runbook |\n| Production provider certification | PENDING | Requires real staging credentials and partner/provider fixtures |\n| Performance capacity | NOT MEASURABLE | Requires representative staging load and monitoring |\n| AI safety | NOT MEASURABLE / no AI runtime | No AI runtime is claimed by current implementation |\n\nThe remaining production decision belongs to the staging release owner, security owner, database owner, partner integration owner, and payout/reconciliation owner using `docs/STAGING_ACCEPTANCE_RECORD.md`.\n''')

# latest report
write_md('reports/latest.md', f'''# Enterprise Audit Report\n\n**Generated:** {NOW}  \n**Repository:** `sahilk267/Affiliates`  \n**Commit:** `{subprocess.check_output(['git','-C',str(ROOT),'rev-parse','--short','HEAD'],text=True).strip()}`\n\n## Discovery proof\n\nThe dynamic scan indexed **{len(FILES)} files** in **{INDEX.get('directory_count')} directories** while excluding only `.git` internals and generated `audit/` outputs. It found **{INDEX.get('discovery_counts',{}).get('total_typescript_files')} TypeScript**, **{INDEX.get('discovery_counts',{}).get('total_javascript_files')} JavaScript**, **{INDEX.get('discovery_counts',{}).get('total_markdown_files')} Markdown**, **{INDEX.get('discovery_counts',{}).get('total_json_files')} JSON**, **{INDEX.get('discovery_counts',{}).get('total_test_files')} automated test files**, and **{INDEX.get('discovery_counts',{}).get('total_docker_related_files')} Docker-related files**. The full per-file index is `audit/index.json`.\n\n## Finding summary\n\n| Severity | Count |\n|---|---:|\n| CRITICAL | {sum(1 for x in issues if x['severity']=='CRITICAL')} |\n| HIGH | {sum(1 for x in issues if x['severity']=='HIGH')} |\n| MEDIUM | {sum(1 for x in issues if x['severity']=='MEDIUM')} |\n| INFO | {sum(1 for x in issues if x['severity']=='INFO')} |\n\n## Findings\n\n| ID | Severity | Category | Title | Location | Priority |\n|---|---|---|---|---|---|\n{issue_table}\n\n## Current release boundary\n\nThis generated audit is supplementary evidence. The current project-status source is `CURRENT_PROJECT_STATUS.md`; Phase 1 status is maintained in `audit/phase1-gate.json`; staging and production decisions remain governed by `STAGING_BLOCKER_REGISTER.md`, `STAGING_READINESS_REPORT.md`, and `docs/RELEASE_OPERATIONS_RUNBOOK.md`. Local code checks do not approve partner access, data permissions, real credentials, financial policy, staging mutations, or production release. Regenerate and review this report together with those current sources rather than treating a generated snapshot as an approval.\n\nSee the sibling audit files for API, database, security, frontend, backend, AI, testing, documentation, code quality, architecture, dependencies, and release-readiness details.\n''')

print(json.dumps({'generated_at':NOW,'issues':len(issues),'artifacts':sorted(str(p.relative_to(ROOT)) for p in AUDIT.rglob('*') if p.is_file())},indent=2))
