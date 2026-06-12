# Deploy — pos.varitaxes.com

## GitHub repository: `varitaxes-pos`

Auto-deploy runs on every push to `main` via GitHub Actions (FTP).

### 1. Create GitHub repo (one time)

1. Go to https://github.com/new
2. Repository name: **varitaxes-pos**
3. Do **not** add README (project already has one)
4. Create repository

### 2. Push local code

```bash
cd c:\xampp\htdocs\pos
git remote add origin https://github.com/YOUR_GITHUB_USERNAME/varitaxes-pos.git
git branch -M main
git push -u origin main
```

### 3. GitHub Actions secrets (required)

In repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Value |
|--------|--------|
| `FTP_SERVER` | `ftp.varitaxes.com` |
| `FTP_USERNAME` | `u149761999.pos` |
| `FTP_PASSWORD` | *(your FTP password — never commit)* |
| `FTP_SERVER_DIR` | `./` (not needed in workflow — FTP login root is already `public_html/pos`) |

> Account `u149761999.pos` opens directly in the subdomain folder. **Do not** use `public_html/pos/` or nested folders will be created.

### 4. Server setup (Hostinger / one time)

1. **MySQL**: Create database + user in hPanel, import `database/schema.sql` then `database/seed.sql`
2. **Config**: Create `config/database.local.php` on server (see `config/database.production.example.php`)
3. **PHP**: 8.1+, enable `pdo_mysql`, `gd`, `mbstring`
4. **Document root**: Must point to the `pos` folder (where `index.php` lives)
5. **`.htaccess`**: Ensure `mod_rewrite` is enabled

### 5. Production URL

https://pos.varitaxes.com/

Set `APP_ENV=production` in hosting panel if available (disables debug messages).

### Security

- Rotate FTP password after adding it to GitHub Secrets only
- Never commit `database.local.php` or passwords to git
