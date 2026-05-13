# TRYL E-Commerce Core & Shop Updates

## 🌟 System Overview & Capabilities

The TRYL ecosystem is a fully custom, high-performance e-commerce engine built on top of WordPress and WooCommerce. It replaces standard, clunky WordPress elements with a sleek, Nike-inspired technical streetwear aesthetic.

Here is everything your system can currently do:

1.  **Premium 3D Shop Grids:** Displays your products in a highly customized, flat-minimalist grid with an interactive 3D tilt effect on product images.
2.  **Global Theme Switcher:** Allows users to instantly toggle the entire site between "Bright", "Mild", and "Dark" modes, automatically saving their preference. It also detects their device's native OS preference on their first visit.
3.  **AJAX Mini-Cart Drawer:** A slide-out cart that updates instantly without reloading the page when a user clicks "Add to Cart".
4.  **Optimized Checkout Flow:** A heavily customized, distraction-free WooCommerce Cart and Checkout experience.
5.  **Printful Automation:** A secure webhook listener that automatically marks WooCommerce orders as "Completed" and saves tracking numbers when Printful prints and ships a product.
6.  **Integrated Prayer Request System:** A custom dashboard inside WordPress where you can read, manage, and directly reply to user prayer requests. It also sends automated comfort emails to users upon submission.
7.  **LokServices Bridge:** A secure deployment portal allowing remote code updates without needing FTP access.
8.  **Centralized Dev Dashboard:** A simple UI in the WordPress admin to manage settings, nav links, themes, and external API keys without touching code.

---

## 📖 Professional How-To Guide

### 1. Managing the TRYL Settings (Dev Dashboard)
**What it does:** Allows you to control global variables like links, limits, and themes.
**How to use it:**
1. Log into your WordPress Admin Dashboard.
2. Click on **TRYL Settings** in the left sidebar.
3. Here you can:
   - Set the **Default Theme Mode**.
   - Update the **Header Logo URL** (paste an image link here to override text).
   - Change the **Prayer Request Notification Email** (who gets alerted when a prayer is sent).
   - Update **Global Navigation Links** (Mission, Contact, etc.).
   - Add your **Printful Webhook Token**.
4. Click **Save TRYL Settings**.

### 2. Managing Prayer Requests
**What it does:** Acts as a mini-inbox for user prayers, allowing direct replies.
**How to use it:**
1. Log into WordPress and click on **Prayers** (the heart icon) in the sidebar.
2. You will see a list of all submitted prayers and their status (Pending or Replied).
3. Click on a prayer to open it.
4. Read the user's message in the "Prayer Details & Response" box.
5. If they provided an email, type your response in the text area and click **Send Reply & Save**. The user will instantly receive an email from your store.

### 3. Setting Up Printful Automated Fulfillment
**What it does:** Automatically completes WooCommerce orders when Printful ships them.
**How to use it:**
1. Go to **TRYL Settings** in WordPress.
2. Scroll down to "External Integrations" and enter a secure, random phrase into the **Printful Webhook Token** field (e.g., `LokSecure123`). Save it.
3. Log into your **Printful Dashboard**.
4. Navigate to **Settings > API > Webhooks**.
5. Set your Webhook URL to: `https://yourwebsite.com/wp-json/tryl/v1/printful-sync?token=LokSecure123`
6. Check the boxes for **Package shipped**, **Package returned**, and **Order failed/canceled**.

### 4. Displaying the Shop Grids
**What it does:** Renders your products on the frontend.
**How to use it:**
*   **Primary 3D Shop:** Use the shortcode `[tryl_3d_shop]` on any page, OR apply the "Righteous Yield Master Grid" page template to a WordPress page.
*   **Luxury Editorial Skin:** Use the shortcode `[tryl_shop_editorial]` on any page for a softer, editorial-style layout.

### 5. Using the LokServices Bridge (For Developers)
**What it does:** Allows instant remote deployment of code files.
**How to use it:**
1. In WordPress, go to **Settings > LokServices Bridge**.
2. Copy your **Secret API Key** and **API Endpoint URL**.
3. On your local computer, open terminal and run the Python deployer script:
   `python lok_deploy.py`
4. Paste your API key into the script when prompted (or edit the file to save it).
5. Provide the local file path and the remote WordPress path to instantly overwrite live files securely.

### 6. Changing the Global Logo
**What it does:** Swaps the text "The Righteous Yield Life" for your brand image.
**How to use it:**
1. Go to **Media > Add New** in WordPress and upload your logo (PNG or SVG recommended).
2. Click the image and copy the **File URL**.
3. Go to **TRYL Settings**.
4. Paste the URL into the **Header Logo URL** box and save.

---

## Deployment Guide (How to upload without breaking anything)

1. **Backup First:** Always take a full backup of your WordPress site (both files and database) before pushing new code. You can use plugins like UpdraftPlus, or your host's built-in backup tool.
2. **Upload via FTP or Host File Manager:**
   - Navigate to `wp-content/plugins/tryl-ecommerce-core/` and overwrite the existing files with the new ones (including the new `templates/single-product.php` and `CHANGELOG.md`).
   - Navigate to `wp-content/themes/tryl-theme/` (and/or your Divi child theme) and overwrite the `page-righteous-shop.php` file.
3. **Clear Caches:** Once uploaded, immediately clear your WordPress caching plugins (e.g., LiteSpeed, WP Rocket, SiteGround Optimizer) and your browser cache (Ctrl+F5 / Cmd+Shift+R). 

## Troubleshooting

* **Site Shows a "Critical Error" or White Screen:**
  If you accidentally upload an incomplete file or miss a bracket, WordPress might throw a fatal error. To quickly fix this and regain access, log into your FTP or cPanel File Manager and rename the `tryl-ecommerce-core` folder to `tryl-ecommerce-core-disabled`. This turns off the plugin and restores your site. Then, re-upload the files carefully.
* **Visual Changes or "Buy Now" Buttons Aren't Updating:**
  This is almost always a caching issue. Make sure to purge your server cache, CDN cache (like Cloudflare), and local browser cache.
* **Mini Cart Drawer Isn't Sliding Open:**
  Open your browser's Developer Tools (F12) and check the "Console" tab. If you see JavaScript errors from other plugins, they might be blocking the cart script. Also, ensure your active theme includes the `wp_footer()` function at the very bottom of `footer.php`, which is required to load the drawer HTML.
* **"Add to Cart" Button is Missing on the New Single Product Page:**
  WooCommerce automatically hides the Add to Cart button if a product does not have a price set, or if it is marked "Out of Stock". Check the product data in the WordPress admin to ensure a price is entered.
* **Single Product Layout Looks Unstyled/Messy:**
  The new single product template relies on Tailwind CSS via CDN. If your site has strict security headers blocking external scripts, the Tailwind CDN script might be blocked. You can verify this in the browser console.