# WDJ Download Codes

**Author:** WebDevJohn  
**License:** GPL-2.0+  
**Status:** Active – v0.1 baseline

---

## Overview

WDJ Download Codes provides a lightweight system for managing and tracking digital download codes inside WordPress.  
It lets you upload batches of unique codes, distribute them through newsletter forms, and monitor redemptions in the admin.

Ideal for artists, creators, or site owners who want a simple way to manage album or digital download codes directly from their website.

---

## Features

- Upload code batches from **CSV** or **TXT** files
- Automatic code assignment on **WPForms** submission
- Customizable email delivery templates
- Admin dashboard for issued and redeemed codes
- Stores all codes in a dedicated `$wpdb` table for stability
- Optional database purge on uninstall

---

## Installation

1. Upload or clone this repository into your WordPress `wp-content/plugins/` directory
2. Activate **WDJ Download Codes** from **Plugins → Installed Plugins**
3. Navigate to **Settings → Download Codes**
4. Upload a CSV/TXT file with one code per line
5. Configure integration with your **WPForms** form

---

## Usage

When a user submits your connected form:
- The plugin assigns the next available unused code
- Stores the user’s email and timestamp
- Sends a customized email with their download link or instructions

Codes and redemptions can be viewed in **Download Codes → Manage Codes**.

Database table used:
```
[download_codes]
```
Columns include `id`, `code`, `email`, `date_redeemed`, and `status`.

---

## Current Limitations

- WPForms is required for automatic assignment
- One code per user; duplicate prevention handled by form logic
- No native export yet (planned for later release)

---

## Roadmap

- ✅ File upload and storage
- ✅ Email delivery integration
- ⏳ Export and import tools
- ⏳ REST API endpoints for external apps
- ⏳ Admin bulk actions for reset or resend

---

## Contributing

Contributions and issue reports are welcome once the repository is public.  
Please test changes locally before submitting pull requests.

---

## License

This project is licensed under the **GNU General Public License v2.0 or later (GPL-2.0+)**.  
See the `LICENSE` file for details.
