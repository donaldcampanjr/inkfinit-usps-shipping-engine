# GitHub Security & Configuration for Commercial Plugin

**Inkfinit USPS Shipping Engine**  
**Commercial Plugin Distribution Setup**

---

## 1. Repository Settings (Commercial Protection)

### Branch Protection

1. **Go to:** Settings → Branches → Branch protection rules
2. **Protect the `main` branch:**

   - [x] Require a pull request before merging
   - [x] Require approvals (set to 1)
   - [x] Require status checks to pass
   - [x] Require branches to be up to date
   - [x] Include administrators
   - [x] Restrict who can push to matching branches

### Access Control

1. **Settings → Collaborators and teams**
   - Only add trusted developers
   - Set appropriate permissions (Maintain, Write, Read)
   - Remove anyone no longer working on project

2. **Settings → Security and analysis**
   - [x] Enable Dependabot alerts
   - [x] Enable Dependabot security updates
   - [x] Enable secret scanning
   - [x] Enable secret scanning push protection

---

## 2. Release & Distribution Setup

### Creating Releases

1. **Tag Convention:**
   ```bash
   git tag -a v1.2.0 -m "Release version 1.2.0"
   git push origin v1.2.0
   ```

2. **Release Notes Include:**
   - Features added
   - Bugs fixed
   - Security patches
   - Breaking changes (if any)
   - Installation link (link to Inkfinit website)

### Release Content Template

```markdown
# Inkfinit USPS Shipping Engine v1.2.0

## 🎉 New Features
- Feature 1 description
- Feature 2 description

## 🐛 Bug Fixes
- Fixed issue 1
- Fixed issue 2

## 🔒 Security
- Patched security vulnerability
- Improved data sanitization

## 📦 Installation

**To download this plugin for use:**
Visit https://inkfinit.pro to purchase and download the latest version.

**For developers:**
Clone this repository for development purposes:
```bash
git clone https://github.com/donaldcampanjr/inkfinit.git
```
```

---

## 3. `.gitignore` Configuration

Create or update `.gitignore` in repository root:

```plaintext
# Operating System
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db
.vscode/
.idea/

# Environment Variables
.env
.env.local
.env.*.local
.env.development
.env.production

# WordPress & Database
wp-config.php
wp-config-*.php
wp-content/uploads/
wp-content/cache/
wp-content/backup-*
.htaccess

# Composer
composer.lock
vendor/

# NPM (if using)
node_modules/
package-lock.json
yarn.lock
dist/
build/

# IDE & Editors
.vscode/
.idea/
*.swp
*.swo
*~
.DS_Store

# Testing
phpunit.xml
.phpunit.result.cache
coverage/

# Temporary Files
*.tmp
*.temp
tmp/
temp/

# Credentials (CRITICAL)
credentials.json
secrets.json
api-keys.json
.secrets/

# Local Development
local-dev/
dev-settings.php
debug.log
error.log
```

---

## 4. README.md - Commercial License Notice

Update your main README.md with:

```markdown
# Inkfinit USPS Shipping Engine

Professional USPS Shipping for WooCommerce

## 📋 License

This project is licensed under the **GPL-3.0-or-later** license.

### Important Notice

**This is a commercial product** sold by Inkfinit LLC.

- 📥 **Official Downloads:** Available at https://inkfinit.pro
- 🔗 **WordPress.org:** https://wordpress.org/plugins/inkfinit-shipping-engine/
- 💻 **Development:** This GitHub repository is provided for:
  - Community contributions and improvements
  - Self-hosted deployments
  - Development and testing

### What This Means

✅ **You Can:**
- Use this plugin for your own store
- Modify the code for your needs
- Contribute improvements back
- Distribute under GPL-3.0-or-later terms
- Access source code for transparency

❌ **You Cannot:**
- Resell this plugin without permission
- Remove or modify copyright notices
- Restrict source code distribution
- Claim authorship/copyright

### Support & Updates

- 🎯 **Official Support:** https://inkfinit.pro/support
- 🐛 **Bug Reports:** https://github.com/donaldcampanjr/inkfinit/issues
- 💬 **Community:** WordPress.org plugin forums

### Purchase

For official releases and support, purchase through:
**https://inkfinit.pro**

---

## Building from Source

```bash
git clone https://github.com/donaldcampanjr/inkfinit.git
cd inkfinit
# Plugin is ready to use in wp-content/plugins/
```

## Contributing

Contributions are welcome! Please see CONTRIBUTING.md for guidelines.

## Security Issues

Found a security issue? **Do NOT** open a public issue. Email: security@inkfinit.pro

---
```

---

## 5. Create CONTRIBUTING.md

```markdown
# Contributing to Inkfinit USPS Shipping Engine

We welcome contributions! However, please note this is a commercial product.

## Before Contributing

1. **Check the License:** This project is GPL-3.0-or-later
2. **Sign CLA:** By contributing, you agree contributions can be used in commercial product
3. **Follow Code Standards:** See below

## How to Contribute

### Reporting Bugs

1. Check existing issues first
2. Describe the bug clearly with:
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - WordPress/WooCommerce versions
   - PHP version

### Suggesting Features

1. Open an issue with the feature request tag
2. Explain the use case
3. Describe the proposed implementation
4. Link to any related issues

### Code Contributions

1. **Fork the repository**
2. **Create a feature branch:** `git checkout -b feature/your-feature-name`
3. **Make your changes** following code standards
4. **Test thoroughly** on a clean WordPress install
5. **Commit with clear messages:** `git commit -m "Add feature: description"`
6. **Push to your fork:** `git push origin feature/your-feature-name`
7. **Open a Pull Request** with detailed description

## Code Standards

### PHP

- Follow WordPress coding standards
- Use 4-space tabs (not spaces)
- Add docblocks for functions and classes
- Sanitize and escape all user input
- Add security nonces to forms

### JavaScript

- Use modern ES6+ syntax
- Add comments for complex logic
- Minify for production (assets/admin-*.js.min)
- Use `wp_localize_script` for internationalization

### Git Commits

- Use clear, descriptive commit messages
- Reference issues: "Fixes #123"
- Use present tense: "Add feature" not "Added feature"
- Keep commits focused and logical

## Code Review Process

1. Automated tests run
2. Code review from maintainer
3. Feedback and iterations
4. Approval and merge

## License Agreement

By contributing, you agree that your contributions will be licensed under GPL-3.0-or-later and may be used in the commercial version of this plugin.

---

Thank you for contributing!
```

---

## 6. Security Policy (SECURITY.md)

```markdown
# Security Policy

## Reporting Security Issues

**Do NOT open public GitHub issues for security vulnerabilities.**

Please report security issues to:
**security@inkfinit.pro**

Include:
- Description of vulnerability
- How to reproduce
- Potential impact
- Suggested fix (if any)

## Supported Versions

| Version | Status | Security Updates |
|---------|--------|------------------|
| 1.2.x   | Current | ✅ Yes |
| 1.1.x   | Outdated | ⚠️ Limited |
| < 1.1   | Unsupported | ❌ No |

## Security Updates

Security updates are released as soon as a fix is available. We recommend:
- Enable automatic plugin updates
- Check WordPress.org for critical security releases
- Subscribe to security notifications

## Known Issues

None currently. Check back for updates.

## Security Considerations

- USPS OAuth v3 API credentials are stored securely
- All API communications use HTTPS
- User data is properly sanitized and escaped
- SQL injection protection via prepared statements
- CSRF protection via WordPress nonces

---
```

---

## 7. `.github/workflows/security.yml` (GitHub Actions)

```yaml
name: Security Checks

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  security:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Run PHP security check
      run: |
        composer require --dev phpstan/phpstan
        vendor/bin/phpstan analyse includes/ --level 5
    
    - name: Check for secrets
      run: |
        pip install detect-secrets
        detect-secrets scan --baseline .secrets.baseline
```

---

## 8. Repository Settings Summary

### Visibility: Public
- Allows community contributions
- Transparent about code quality
- Good for trust

### Branch Protection: Enabled
- Requires PR reviews
- Status checks pass
- Prevents direct commits to main

### Issues: Enabled
- Community bug reports
- Feature requests
- Public discussions

### Discussions: Enabled (Optional)
- Community Q&A
- Showcase integrations
- Share best practices

### Projects: Enabled
- Track development
- Plan releases
- Manage roadmap

---

## 9. Release Workflow

### For Development
```bash
# Create feature branch
git checkout -b feature/my-feature
# Make changes
git add .
git commit -m "Add: my feature"
git push origin feature/my-feature
# Create Pull Request on GitHub
```

### For Production Release
```bash
# Update version in plugin.php
# Update CHANGELOG.md
# Update readme.txt

git add .
git commit -m "Release: v1.2.0"
git tag -a v1.2.0 -m "Release version 1.2.0"
git push origin main
git push origin v1.2.0

# Create GitHub release from tag
# Upload plugin to WordPress.org (if applicable)
```

---

## 10. Monitoring & Maintenance

### Weekly
- Check GitHub issues
- Review pull requests
- Monitor security alerts

### Monthly
- Update dependencies
- Review security advisories
- Plan releases

### Quarterly
- Major version planning
- Architecture review
- Performance optimization

---

## Questions?

For questions about the repository structure or contribution process, open a GitHub issue or contact:
**support@inkfinit.pro**

---

**Last Updated:** December 3, 2025  
**Maintained By:** Inkfinit LLC
```

---

Now create a release template for consistency:

---

## 11. `.github/release-template.md`

```markdown
# Release v[VERSION]

**Release Date:** [DATE]

## 🎉 New Features
- [Feature 1]
- [Feature 2]

## 🐛 Bug Fixes
- [Bug 1]
- [Bug 2]

## 🔒 Security
- [Security fix 1]

## 📊 Statistics
- Files changed: X
- Lines added: Y
- Lines removed: Z

## 📥 Installation

**Official Release:**
Download from https://inkfinit.pro

**WordPress.org:**
https://wordpress.org/plugins/inkfinit-shipping-engine/

**From Source (Development):**
```bash
git clone https://github.com/donaldcampanjr/inkfinit.git
git checkout v[VERSION]
```

## 🙏 Contributors
Thank you to all who contributed to this release!

## 📖 Documentation
- [Installation Guide](https://docs.inkfinit.pro)
- [User Guide](https://inkfinit.pro/user-guide)
- [Developer Documentation](https://docs.inkfinit.pro/dev)

---
```

This comprehensive setup ensures your plugin is ready for WordPress.org while protecting your commercial interests on GitHub.
