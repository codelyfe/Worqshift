# 🐘 Worqshift – PHP Error Handling Plugin for WordPress

Worqshift is a lightweight PHP error tracking plugin for WordPress that logs, displays, and manages PHP runtime errors in real-time — all within your WordPress admin dashboard.

## 🚀 Features

- 📋 **Error Logging**: Automatically captures PHP errors and stores them in a JSON log file.
- 🐞 **Error Feed UI**: Beautiful admin page listing all logged errors with details.
- 📈 **Error Count Badge**: Displays total PHP error count in the admin bar and menu.
- 🧠 **Error Insights**: Includes copy, Google search, and ChatGPT lookup buttons per error.
- 💾 **Export Support**: Export errors as `.json`, `.csv`, or plain `.txt`.
- 🧹 **Clear Logs**: Delete individual or all error logs.
- 💽 **Disk Info Display**: Displays disk usage and server info at a glance.
- 📊 **Dashboard Widget**: Highlights active errors right from the WordPress dashboard.

## 📂 Log File

Errors are logged to:  
`wp-content/php-error-log.json`

Each error entry includes:
- `error`: Full error string with file and line
- `count`: Number of occurrences
- `time`: Last seen timestamp

## 🛠 Admin Interface

Located under:  
**Admin > PHP Error Feed**

- View real-time error list (auto-updates every 5 seconds)
- Highlighted error editor with copy and download tools
- Buttons to search errors on Google or ChatGPT
- Export options and a 'Clear All' feature

## 🔔 Notifications

- Shows total PHP error count in:
  - The WordPress admin bar
  - The sidebar menu next to "PHP Error Feed"
  - The WordPress dashboard widget

## 🔧 Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin via the **Plugins** page.
3. Navigate to **Admin > PHP Error Feed** to begin monitoring.

## ✅ On Activation

- Creates the initial `php-error-log.json` file if not present.
- Sets up WordPress hooks for logging, displaying, and managing errors.

## 📦 Export Options

- **TXT**: Downloads a formatted error summary.
- **JSON**: Full error object export.
- **CSV**: Clean table-ready export with columns: Error, Count, Last Seen.

## 📌 Notes

- This plugin is ideal for debugging during development or on staging servers.
- Not intended as a replacement for full server-side error monitoring tools.

---

**Author:** [codelyfe.github.io](https://codelyfe.github.io)  
**Version:** 1.4  

