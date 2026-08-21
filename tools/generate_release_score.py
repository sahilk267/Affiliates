#!/usr/bin/env python3
import json, re
from pathlib import Path
from datetime import datetime, timezone
ROOT=Path('/home/ubuntu/Affiliates'); AUDIT=ROOT/'audit'
idx=json.loads((AUDIT/'index.json').read_text()); stats=json.loads((AUDIT/'statistics.json').read_text()); issues=json.loads((AUDIT/'issues.json').read_text())['issues']; arch=json.loads((AUDIT/'architecture.json').read_text()); api=json.loads((AUDIT/'api.json').read_text()); db=json.loads((AUDIT/'database.json').read_text())
files={f['path']:f for f in idx['files']}
def s(p):
    try:return (ROOT/p).read_text(errors='replace')
    except:return ''
def has(p,needle): return needle in s(p)
def has_any(paths, needle): return any(needle in s(p) for p in paths)
php_paths=[f['path'] for f in files.values() if f['language']=='PHP']
def count(pattern, paths=None):
    rx=re.compile(pattern)
    chosen=paths or files
    return sum(len(rx.findall(s(p))) for p in chosen)
def score(name, controls):
    measured=[c for c in controls if c['measurable']]
    if not measured:return {'score':'NOT MEASURABLE','earned':None,'possible':None,'controls':controls}
    earned=sum(1 for c in measured if c['passed']); possible=len(measured)
    return {'score':round(100*earned/possible,2),'earned':earned,'possible':possible,'controls':controls}

categories={}
categories['Architecture']=score('Architecture',[{'name':'frontend','passed':arch['present']['frontend'],'measurable':True},{'name':'backend','passed':arch['present']['backend'],'measurable':True},{'name':'api','passed':arch['present']['api'],'measurable':True},{'name':'database','passed':arch['present']['database'],'measurable':True},{'name':'service layer','passed':arch['present']['service_layer'],'measurable':True},{'name':'kernel','passed':arch['present']['kernel'],'measurable':True},{'name':'security/auth/authorization','passed':arch['present']['security'] and arch['present']['authentication'] and arch['present']['authorization'],'measurable':True},{'name':'infrastructure','passed':arch['present']['infrastructure'],'measurable':True},{'name':'queue/workers','passed':False,'measurable':True},{'name':'adapters/plugins/agents','passed':False,'measurable':True}])
sec_controls=[('web CSRF',has('app/Http/Kernel.php','VerifyCsrfToken')),('password hashing',has('app/User.php',"'password' => 'hashed'") or has('app/Http/Controllers/AuthController.php','Hash::make')),('request validation',count(r'->validate\(|Validator::make')>0),('rate limiting',has('app/Http/Kernel.php','ThrottleRequests')),('file upload validation',count(r'mimes:|image\|', [f['path'] for f in files.values() if f['path'].endswith('.php')])>0),('API authentication',has('app/Http/Middleware/VerifyPartnerSignature.php','hash_hmac') and has('routes/api.php','partner.signature')),('auth mechanism consistency',has('app/Http/Controllers/AuthController.php','Auth::login') and has('app/Http/Middleware/AdminMiddleware.php','Auth::user')),('default secret hygiene',has('app/Database/Seeders/AdminUserSeeder.php','ADMIN_PASSWORD') or has('database/seeders/AdminUserSeeder.php','ADMIN_PASSWORD')),('security headers',has('app/Http/Middleware/SecurityHeaders.php','X-Content-Type-Options')),('brute-force control',has('app/Http/Kernel.php','ThrottleRequests') and has('routes/api.php','throttle:affiliate-'))]
categories['Security']=score('Security',[{'name':n,'passed':p,'measurable':True} for n,p in sec_controls])
test_controls=[('test files',idx['file_count']>0 and idx['discovery_counts']['total_test_files']>0),('coverage artifact',has('.github/workflows/ci.yml','--coverage-clover') and has('.github/workflows/ci.yml','upload-artifact')),('CI fails on test failure',not has('.github/workflows/ci.yml','|| true')),('migration tests',has('tests/Feature/ReleaseBlockingControlsTest.php','RefreshDatabase')),('API/security tests',has('tests/Feature/ReleaseBlockingControlsTest.php','assertUnauthorized') and has('tests/Feature/ReleaseBlockingControlsTest.php','idempotent'))]
categories['Testing']=score('Testing',[{'name':n,'passed':p,'measurable':True} for n,p in test_controls])
doc_controls=[('README', 'README.md' in files),('changelog','CHANGELOG.md' in files),('deployment guidance',has('docs/RELEASE_OPERATIONS_RUNBOOK.md','Migration rehearsal') and has('README.md','Deployment Notes')),('environment template',any(f['filename']=='.env.example' for f in files.values())),('API contract',bool(api['openapi_files'])),('architecture docs',(ROOT/'docs/architecture.md').exists()),('ADR directory',(ROOT/'adr').exists() or (ROOT/'docs/adr').exists())]
categories['Documentation']=score('Documentation',[{'name':n,'passed':p,'measurable':True} for n,p in doc_controls])
categories['Performance']=score('Performance',[{'name':'runtime profiling','passed':False,'measurable':False},{'name':'load test','passed':False,'measurable':False}])
mi=stats['complexity']['average_maintainability_index_heuristic']; categories['Maintainability']={'score':mi,'earned':mi,'possible':100,'controls':[{'name':'static maintainability heuristic','passed':mi,'measurable':True}]}
scale_controls=[('pagination',count(r'->paginate\(',[f['path'] for f in files.values() if f['language']=='PHP'])>0),('indexes',len(db['indexes_and_foreign_keys'])>0),('cache/redis',has('config/cache.php','redis') or has('config/database.php','redis')),('queues',arch['present']['queue']),('background workers',arch['present']['workers']),('rate limiting',has('app/Http/Kernel.php','ThrottleRequests'))]
categories['Scalability']=score('Scalability',[{'name':n,'passed':p,'measurable':True} for n,p in scale_controls])
rel_controls=[('health endpoints',has('routes/web.php',"'/health'") and has('routes/api.php',"'/health'")),('error logging',count(r'Log::(error|warning|info)')>0),('transactional points operations',count(r'DB::transaction')>0),('clean migration order',len(db['migration_order_violations'])==0),('automated tests',idx['discovery_counts']['total_test_files']>0)]
categories['Reliability']=score('Reliability',[{'name':n,'passed':p,'measurable':True} for n,p in rel_controls])
categories['AI Safety']=score('AI Safety',[{'name':'AI runtime exists to audit','passed':False,'measurable':False}])
devops_controls=[('CI workflow',any(f['path'].startswith('.github/workflows/') for f in files.values())),('Docker assets',idx['discovery_counts']['total_docker_related_files']>0),('environment template',any(f['filename']=='.env.example' for f in files.values())),('monitoring',has('docs/RELEASE_OPERATIONS_RUNBOOK.md','Observability and alerting')),('backups',any('backup' in f['path'].lower() for f in files.values())),('rollback/blue-green',any(x in f['path'].lower() for f in files.values() for x in ['rollback','blue-green','kubernetes','terraform']))]
categories['DevOps']=score('DevOps',[{'name':n,'passed':p,'measurable':True} for n,p in devops_controls])
db_controls=[('migration order',len(db['migration_order_violations'])==0),('foreign keys/indexes',len(db['indexes_and_foreign_keys'])>0),('transactions',len(db['transactions'])>0),('schema/runtime alignment',not any(x['id'] in {'DB-002','DB-003','DB-004'} for x in issues)),('repository abstraction',False)]
categories['Database']=score('Database',[{'name':n,'passed':p,'measurable':True} for n,p in db_controls])
api_controls=[('routes discovered',api['endpoint_count']>0),('request validation detected',count(r'->validate\(|Validator::make')>0),('auth on mutation APIs',has('routes/api.php','partner.signature')),('rate limiting',has('routes/api.php','throttle:')),('OpenAPI contract',bool(api['openapi_files'])),('automated API tests',idx['discovery_counts']['total_test_files']>0)]
categories['API']=score('API',[{'name':n,'passed':p,'measurable':True} for n,p in api_controls])
frontend_controls=[('Blade views',any(f['path'].startswith('resources/views/') for f in files.values())),('named-route usage',count(r"route\(['\"]",[f['path'] for f in files.values() if f['path'].startswith('resources/views/')])>0),('automated accessibility checks',False),('automated SEO checks',False),('compiled asset pipeline',any(f['filename']=='package.json' for f in files.values()))]
categories['Frontend']=score('Frontend',[{'name':n,'passed':p,'measurable':True} for n,p in frontend_controls])
backend_controls=[('controllers',arch['present']['backend']),('services',arch['present']['service_layer']),('dependency injection',count(r'__construct\(')>0),('validation',count(r'->validate\(|Validator::make')>0),('centralized repositories',False),('centralized error handling',count(r'Log::error')>0)]
categories['Backend']=score('Backend',[{'name':n,'passed':p,'measurable':True} for n,p in backend_controls])
obs_controls=[('application logging',count(r'Log::(info|warning|error)')>0),('health checks',has('routes/web.php',"'/health'") and has('routes/api.php',"'/health'")),('metrics/monitoring',arch['present']['monitoring'])]
categories['Observability']=score('Observability',[{'name':n,'passed':p,'measurable':True} for n,p in obs_controls])
measured=[v['score'] for v in categories.values() if isinstance(v['score'],(int,float))]
overall=round(sum(measured)/len(measured),2) if measured else 'NOT MEASURABLE'
result={'generated_at':datetime.now(timezone.utc).isoformat(),'scoring_policy':'Each measurable category is the arithmetic percentage of passed explicit repository checks. Categories without runtime evidence are marked NOT MEASURABLE and excluded from the overall arithmetic mean. No subjective weighting is applied.','categories':categories,'release_score_out_of_100':overall,'measurable_category_count':len(measured),'not_measurable_categories':[k for k,v in categories.items() if v['score']=='NOT MEASURABLE']}
(AUDIT/'release-score.json').write_text(json.dumps(result,indent=2)+'\n')
# Append score sections to release readiness and latest report.
rr=(AUDIT/'release-readiness.md').read_text(); rr += '\n\n## Calculated release score\n\n| Category | Score | Basis |\n|---|---:|---|\n' + '\n'.join(f"| {k} | {v['score']} | {v['earned']}/{v['possible']} explicit checks |" if isinstance(v['score'],(int,float)) else f"| {k} | NOT MEASURABLE | No repository/runtime evidence available |" for k,v in categories.items()) + f"\n\n**Overall release score:** **{overall}/100** across {len(measured)} measurable categories. Performance and AI Safety are NOT MEASURABLE and are excluded from the mean.\n"
(AUDIT/'release-readiness.md').write_text(rr)
latest=(AUDIT/'reports/latest.md').read_text(); latest += f"\n\n## Calculated release score\n\n**Release score:** **{overall}/100** across {len(measured)} measurable categories, using the explicit control table in `audit/release-score.json`. Categories without repository/runtime evidence are recorded as **NOT MEASURABLE**, not converted into invented percentages.\n"
(AUDIT/'reports/latest.md').write_text(latest)
print(json.dumps({'release_score_out_of_100':overall,'categories':{k:v['score'] for k,v in categories.items()},'not_measurable':result['not_measurable_categories']},indent=2))
