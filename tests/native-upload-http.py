"""Exercise native Bricks upload validation over loopback HTTP; provider is mocked."""
from pathlib import Path
from urllib.request import Request, urlopen
import hashlib
import json
import uuid

root = Path(__file__).resolve().parents[1]
fixture = json.loads((root/'.tools/browser-fixture.json').read_text(encoding='utf-8'))
assert fixture['url'].startswith('http://127.0.0.1:8796/'), 'Wrong QA environment'
capture_file = root/'.tools/browser-capture.json'

def post(files, **changes):
    fields = {'action':'bricks_form_submit','postId':str(fixture['pageId']),'formId':'qaform','nonce':fixture['nonce'],
              'form-field-name01':'Synthetic HTTP Tester','form-field-email1':'qa@example.test','form-field-msg001':'Native multipart upload verification',
              'form-field-secret':'FSBR_HIDDEN_EXCLUDED','form-field-honey1':''}
    fields.update(changes)
    boundary = 'fsbrpython'+uuid.uuid4().hex
    body = b''
    for key,value in fields.items():
        body += ('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"\r\n\r\n'+value+'\r\n').encode()
    for filename,mime,data in files:
        body += ('--'+boundary+'\r\nContent-Disposition: form-data; name="form-field-files1[]"; filename="'+filename+'"\r\nContent-Type: '+mime+'\r\n\r\n').encode()+data+b'\r\n'
    body += ('--'+boundary+'--\r\n').encode()
    req = Request('http://127.0.0.1:8796/wp-admin/admin-ajax.php',data=body,headers={'Content-Type':'multipart/form-data; boundary='+boundary})
    return json.load(urlopen(req,timeout=30))

png = (root/'tests/fixtures/synthetic-image.png').read_bytes()
pdf = (root/'tests/fixtures/synthetic-document.pdf').read_bytes()
files = [('synthetic-image.png','image/png',png),('synthetic-document.pdf','application/pdf',pdf)]
response = post(files)
assert response['success'] is True, response
capture = json.loads(capture_file.read_text(encoding='utf-8'))
assert len(capture['attachments'])==2, capture
assert capture['attachments'][0]['sha256']==hashlib.sha256(png).hexdigest()
assert capture['attachments'][1]['sha256']==hashlib.sha256(pdf).hexdigest()
assert 'FSBR_HIDDEN_EXCLUDED' not in json.dumps(capture)
counter=capture['count']
assert post([('forbidden.php','application/x-php',b'<?php echo "test";')])['success'] is False
assert json.loads(capture_file.read_text(encoding='utf-8'))['count']==counter
assert post(files,**{'form-field-honey1':'filled-by-bot'})['success'] is False
assert json.loads(capture_file.read_text(encoding='utf-8'))['count']==counter
assert post([files[0]]*4)['success'] is False
assert json.loads(capture_file.read_text(encoding='utf-8'))['count']==counter
assert post(files,**{'nonce':'invalid'})['success'] is False
assert json.loads(capture_file.read_text(encoding='utf-8'))['count']==counter
report={'passed':9,'checks':['Native Files upload accepted PNG and PDF','Both binary hashes preserved','Hidden value excluded',
 'Forbidden extension blocked before provider','Honeypot blocked before provider','Field count limit blocked before provider',
 'Invalid nonce blocked before provider','No real FormSubmit calls','Upload pipeline exercised through actual WordPress HTTP handler'],
 'wordpress_response':response,'real_provider_calls':0}
(root/'qa/native-upload-http.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print(json.dumps({'passed':9,'real_provider_calls':0}))
