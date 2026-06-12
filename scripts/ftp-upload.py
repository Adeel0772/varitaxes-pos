#!/usr/bin/env python3
"""FTP upload helper. Set FTP_SERVER, FTP_USERNAME, FTP_PASSWORD, FTP_SERVER_DIR env vars."""
import os
import sys
from ftplib import FTP, error_perm
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SKIP_DIRS = {'.git', '.github', 'tests', '__pycache__', 'node_modules', 'scripts'}
SKIP_FILES = set()


def ftp_cwd(ftp: FTP, path: str) -> None:
    ftp.cwd('/')
    for part in [p for p in path.replace('\\', '/').split('/') if p and p != '.']:
        try:
            ftp.cwd(part)
        except error_perm:
            ftp.mkd(part)
            ftp.cwd(part)


def main() -> int:
    host = os.environ.get('FTP_SERVER', 'ftp.varitaxes.com')
    user = os.environ.get('FTP_USERNAME', '')
    password = os.environ.get('FTP_PASSWORD', '')
    remote_base = os.environ.get('FTP_SERVER_DIR', 'public_html/pos').strip('/')

    if not user or not password:
        print('Set FTP_USERNAME and FTP_PASSWORD.', file=sys.stderr)
        return 1

    print(f'Connecting to {host} as {user}...')
    ftp = FTP(host, timeout=180)
    ftp.login(user, password)
    ftp.set_pasv(True)

    uploaded = 0
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [
            d for d in dirnames
            if d not in SKIP_DIRS and not d.startswith('.')
        ]

        rel_dir = Path(dirpath).relative_to(ROOT).as_posix()
        if rel_dir == '.':
            remote_dir = remote_base
        else:
            remote_dir = f'{remote_base}/{rel_dir}'

        try:
            ftp_cwd(ftp, remote_dir)
        except error_perm as e:
            print(f'SKIP dir {rel_dir}: {e}', file=sys.stderr)
            continue

        for name in filenames:
            if name.startswith('.') and name != '.htaccess':
                continue
            local_file = Path(dirpath) / name
            rel_file = local_file.relative_to(ROOT).as_posix()
            if rel_file in SKIP_FILES:
                continue
            try:
                with open(local_file, 'rb') as f:
                    ftp.storbinary(f'STOR {name}', f)
                uploaded += 1
                if uploaded % 50 == 0:
                    print(f'  ... {uploaded} files ({rel_file})')
            except (error_perm, OSError) as e:
                print(f'FAIL {rel_file}: {e}', file=sys.stderr)

    ftp.quit()
    print(f'Done. Uploaded {uploaded} files to /{remote_base}/')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
