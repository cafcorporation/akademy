# Akdemy AI — Cost Engineering Campus

E-learning platform of CAF Corporation on **WordPress + LearnPress**, with course content written as code in this repository and published by Claude.

## How it works

```
courses/<slug>/course.yml      structure, pricing, rewards (single source of truth)
courses/<slug>/es|en/*.md      lessons (Markdown, one file per lesson and language)
courses/<slug>/quiz/*.yml      quizzes (bilingual)
courses/<slug>/video/es|en/    video scripts for recording
        │  npm run build   (tools/build-course.mjs)
        ▼
build/<slug>-<lang>.json       one package per language
        │  wp akdemy import   (Akdemy Bridge plugin → LearnPress abilities)
        ▼
LearnPress course (published, idempotent: re-running replaces the previous import)
```

## Local site (LocalWP)

- Site **AkdemyAI** → http://localhost:10010 (admin: `/wp-admin`)
- Port 80 is taken by XAMPP/Herd on this PC, so the site runs on its own port.
- The Local site must be running (green dot in the Local app).
- `wordpress/plugins/akdemy-bridge` is linked into the site with a directory junction — edit it here.

## Commands

```bash
npm install
npm run publish:local                       # build + publish all courses
powershell -File scripts/wp.ps1 <command>   # WP-CLI against the local site
```

## Brand

CAF Corporation palette — Orange `#F5A011`, Dark Gray `#585856`, Cream `#F8F6F2`, Text `#1A1A1A`; Calibri/Arial.
Logos in `brand/` (source: SharePoint `SIG/5-Logo/Logo CAF Akademy`). Styles in `wordpress/plugins/akdemy-bridge/assets/akdemy-brand.css`.

`docker-compose.yml` is an alternative environment (requires WSL, not installed on this PC).
