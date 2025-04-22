# Gravity Forms HubSpot Integration

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPLv2%2Bor_later-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

A lightweight WordPress plugin to send Gravity Forms submissions straight into HubSpot.  
Assign a Portal ID and a HubSpot Form ID to each of your Gravity Forms, and every entry will be posted automatically to the corresponding HubSpot form endpoint.

---

![ChatGPT-Image-22-апр -2025-г ,-20_45_39](https://github.com/user-attachments/assets/b4ac2856-9857-4c7c-822d-ec0ddbd0b0f0)

---
![plugin_screen](https://github.com/user-attachments/assets/ea6de9d4-a448-488b-bc59-3e06359a8dbb)

---

## Features

🔌 **Per‑form configuration**  
  – On your Gravity Forms menu, specify HubSpot Portal & Form IDs for each form.  
🚀 **Seamless integration**  
  – Submissions are sent via the official HubSpot Forms API (`/submissions/v3/integration/submit`).  
🎯 **Automatic field mapping**  
  – Looks for fields labeled “email” and “fullname” in your form; you can extend it for other fields.  
🛠️ **Simple, zero‑config script enqueue**  
  – Loads only jQuery in the admin for settings page behavior.  

---

## Installation

1. Clone or download this repository into your  
   `wp-content/plugins/gravity-forms-hubspot-integration` folder.  
2. Activate **Gravity Forms** (required) and then **Gravity Forms HubSpot Integration**.  
3. Go to **Forms → HubSpot Integration** in the admin menu.  
4. For each form, enter your **Portal ID** and **HubSpot Form ID**, then click **Save**.

---

## Usage

1. User submits a Gravity Form on the front‑end.  
2. The plugin hooks into `gform_after_submission`, builds a JSON payload with `fullname` and `email`, and sends it to:  

https://api.hsforms.com/submissions/v3/integration/submit/{PORTAL_ID}/{HUBSPOT_FORM_ID}

3. Check your HubSpot account — new contacts should appear automatically under the submitted form.

---

## Extending

- **Add more fields**: In `handle_form_submission()`, extend the `$payload['fields']` array.  
- **Custom context**: Add extra data under `$payload['context']` (e.g. page title, user IP).  
- **Error handling**: Wrap `wp_remote_post()` in `try { … } catch` or check the response code for retries/logging.

---

## License

This plugin is released under the **GNU General Public License v2 or later** (GPL‑2.0‑or‑later).  
