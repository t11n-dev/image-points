# 📌 Image Points

![WordPress](https://img.shields.io/badge/WordPress-6.9-blue?logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple?logo=php)
![License](https://img.shields.io/badge/License-GPLv3-green)
![Version](https://img.shields.io/badge/Version-1.0.0-orange)

> Add interactive hotspot pins to your images with drag & drop. Fully responsive. Zero bloat.

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🖱️ **Drag & Drop** | Place pins on images intuitively in the admin |
| 📝 **Rich Tooltips** | TinyMCE editor for HTML content in each pin |
| 🎨 **Custom Icons** | Upload different pin & hover icons per point |
| 🔗 **Link Support** | Add URLs with `_self` / `_blank` target per pin |
| 📱 **Responsive** | Percentage-based positioning — pins stay accurate at any screen size |
| 💫 **Pulse Animation** | Optional pulse effect to draw attention |
| 🧭 **8 Directions** | Tooltip placement: N, S, E, W, NE, NW, SE, SW with smart auto-placement |
| 📋 **Shortcode** | Simple embed: `[image_points id="123"]` |
| 🌐 **i18n Ready** | Full `.pot` file included for translations |
| ⚡ **Lightweight** | No frameworks. jQuery + PowerTip only |

---

## 🚀 Quick Start

### 1. Install

```bash
# Upload to WordPress plugins directory
wp-content/plugins/image-points/
```

Or install via **Plugins → Add New → Upload Plugin** in WordPress admin.

### 2. Create a Hotspot

1. Go to **Image Points → Add New**
2. Upload a **pin icon** image
3. Upload your **main image**
4. Click **Add Point** → drag pin to desired position
5. Click pin to edit **tooltip content, links, placement**
6. **Publish** and copy the shortcode

### 3. Display

```
[image_points id="YOUR_POST_ID"]
```

Paste the shortcode in any post, page, or widget.

---

## 📐 How It Works

```
┌─────────────────────────────────────────┐
│  .wrap_svl (position: relative)         │
│  ┌───────────────────────────────────┐  │
│  │  <img> Main Image (width: 100%)   │  │
│  │                                   │  │
│  │         ● Pin 1 (top:35%, left:62%)  │
│  │                                   │  │
│  │    ● Pin 2 (top:20%, left:40%)    │  │
│  │                                   │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘

Admin: Drag → px converted to % → saved to DB
Frontend: Read % from DB → CSS position: absolute
```

Pins use **percentage coordinates** relative to the image container, ensuring they stay in the correct position at any viewport size.

---

## 🎨 Customization

### Change tooltip colors

```css
/* Tooltip background */
#powerTip {
    background-color: #ffffff;
    color: #333333;
}

/* Arrow colors by direction */
#powerTip.n:before { border-top-color: #ffffff; }
#powerTip.s:before { border-bottom-color: #ffffff; }
#powerTip.e:before { border-right-color: #ffffff; }
#powerTip.w:before { border-left-color: #ffffff; }
```

### Per-pin options

Each pin supports:
- Custom **pin icon** + **hover icon**
- Custom **CSS ID** and **CSS class**
- **ALT text** for accessibility
- **Link URL** with target setting
- **Tooltip direction** (8 options)

---

## 📁 Project Structure

```
image-points/
├── image-points.php     # Main plugin file
├── admin/
│   ├── css/                   # Admin styles
│   ├── js/                    # Admin JS (drag & drop, TinyMCE)
│   ├── images/                # Admin assets
│   └── inc/
│       ├── cpt-image-points.php              # Custom Post Type
│       ├── add-shortcode-image-points.php  # Shortcode renderer
│       ├── settings.php                  # Plugin settings page
│       └── metabox-donate.php            # Donate metabox
├── frontend/
│   ├── css/                   # Frontend styles + PowerTip CSS
│   └── js/                    # Frontend JS + PowerTip
├── languages/
│   └── image-points.pot # Translation template
├── readme.txt                 # WordPress.org readme
└── license.txt                # GPLv3
```

---

## 🔧 Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| jQuery | Included with WP |

---

## 📄 License

This project is licensed under the [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0).

---

## 🙏 Credits

- [jQuery PowerTip](https://stevenbenner.github.io/jquery-powertip/) — Tooltip engine

---

<p align="center">
  Made for <strong>Image Points</strong>
</p>
