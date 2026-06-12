#!/usr/bin/env python3
"""One-time: set GitHub Actions secrets via API. Requires GITHUB_TOKEN env var."""
import base64
import json
import os
import subprocess
import sys
import urllib.request

try:
    from nacl import encoding, public
except ImportError:
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'pynacl', '-q'])
    from nacl import encoding, public

REPO = 'Adeel0772/varitaxes-pos'
SECRETS = {
    'FTP_SERVER': 'ftp.varitaxes.com',
    'FTP_USERNAME': 'u149761999.pos',
    'FTP_PASSWORD': os.environ.get('FTP_PASSWORD', ''),
    'FTP_SERVER_DIR': './',
}


def get_token() -> str:
    token = os.environ.get('GITHUB_TOKEN', '')
    if token:
        return token
    proc = subprocess.run(
        ['git', 'credential', 'fill'],
        input='protocol=https\nhost=github.com\n\n',
        capture_output=True,
        text=True,
        cwd=os.path.dirname(os.path.dirname(__file__)),
    )
    for line in proc.stdout.splitlines():
        if line.startswith('password='):
            return line.split('=', 1)[1]
    return ''


def api(method: str, url: str, token: str, data: dict | None = None):
    body = json.dumps(data).encode() if data is not None else None
    req = urllib.request.Request(
        url,
        data=body,
        method=method,
        headers={
            'Authorization': f'token {token}',
            'Accept': 'application/vnd.github+json',
            'Content-Type': 'application/json',
        },
    )
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode())


def encrypt(public_key_b64: str, secret: str) -> str:
    pk = public.PublicKey(public_key_b64.encode(), encoding.Base64Encoder())
    sealed = public.SealedBox(pk).encrypt(secret.encode('utf-8'))
    return base64.b64encode(sealed).decode('utf-8')


def main() -> int:
    token = get_token()
    if not token:
        print('No GitHub token', file=sys.stderr)
        return 1
    if not SECRETS['FTP_PASSWORD']:
        SECRETS['FTP_PASSWORD'] = os.environ.get('FTP_PASSWORD', '')
    if not SECRETS['FTP_PASSWORD']:
        print('Set FTP_PASSWORD env var', file=sys.stderr)
        return 1

    key_data = api(
        'GET',
        f'https://api.github.com/repos/{REPO}/actions/secrets/public-key',
        token,
    )
    key_id = key_data['key_id']
    key = key_data['key']

    for name, value in SECRETS.items():
        api(
            'PUT',
            f'https://api.github.com/repos/{REPO}/actions/secrets/{name}',
            token,
            {
                'encrypted_value': encrypt(key, value),
                'key_id': key_id,
            },
        )
        print(f'Secret set: {name}')

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
