#!/usr/bin/env python3
"""One-time FTP upload helper. Usage: set env vars FTP_* then run. NOT for committing passwords."""
import os
import sys
from ftplib import FTP, error_perm
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SKIP_DIRS = {'.git', '.github', 'tests', '__pycache__', 'node_modules'}
SKIP_FILES = {'scripts/ftp-upload.py', 'DEPLOY.md'}


def upload_dir(ftp: FTP, local: Path, remote: str) -> None:
    try:
        ftp.cwd(remote)
    except error_perm:
        ftp.mkd(remote)
        ftp.cwd(remote)

    for item in local.iterdir():
        rel = item.relative_to(ROOT).as_posix()
        if item.name in SKIP_DIRS or rel in SKIP_FILES:
            continue
        if item.name.startswith('.') and item.name not in ('.htaccess',):
            continue

        remote_name = item.name
        if item.is_dir():
            upload_dir(ftp, item, remote_name)
            ftp.cwd('..')
        else:
            with open(item, 'rb') as f:
                ftp.storbinary(f'STOR {remote_name}', f)
            print(f'  UP {rel}')


def main() -> int:
    host = os.environ.get('FTP_SERVER', 'ftp.varitaxes.com')
    user = os.environ.get('FTP_USERNAME', '')
    password = os.environ.get('FTP_PASSWORD', '')
    remote_dir = os.environ.get('FTP_SERVER_DIR', '/public_html/pos')

    if not user or not password:
        print('Set FTP_USERNAME and FTP_PASSWORD environment variables.', file=sys.stderr)
        return 1

    print(f'Connecting to {host} as {user}...')
    ftp = FTP(host, timeout=120)
    ftp.login(user, password)
    ftp.set_pasv(True)

    for part in remote_dir.strip('/').split('/'):
        try:
            ftp.cwd(part)
        except error_perm:
            ftp.mkd(part)
            ftp.cwd(part)

    print(f'Uploading from {ROOT} to /{ftp.pwd()} ...')
    upload_dir(ftp, ROOT, '.')
    ftp.quit()
    print('Done.')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
