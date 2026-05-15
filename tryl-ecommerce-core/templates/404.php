<?php
/**
 * Custom 404 Error Template Override
 * 
 * Part of the TRYL Premium E-Commerce Core
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); // This triggers our injected Global Nav Bar

$shop_url = get_option('tryl_nav_shop');
if ( empty( $shop_url ) ) {
    $shop_url = 'https://therighteousyieldlife.com/the-shop-wip/';
}
?>
<style>
.tryl-404-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 65vh;
    text-align: center;
    padding: 60px 20px;
    background: #f5f8f5;
    font-family: var(--tryl-body-font, 'Inter', sans-serif);
    color: #1a2e1a;
    transition: background 0.3s, color 0.3s;
}
[data-theme="dark"] .tryl-404-wrap { background: #0d1b0f; color: #f5f8f5; }
[data-theme="mild"] .tryl-404-wrap { background: #e6e4df; color: #33322e; }

.tryl-404-glitch {
    font-family: var(--tryl-header-font, 'Barlow Condensed', sans-serif);
    font-size: clamp(6rem, 15vw, 12rem);
    font-weight: 900;
    line-height: 1;
    margin: 0 0 10px;
    color: #0d1b0f;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
[data-theme="dark"] .tryl-404-glitch { color: #f5f8f5; }
[data-theme="mild"] .tryl-404-glitch { color: #33322e; }

.tryl-404-msg { font-size: 1.1rem; font-weight: 500; margin-bottom: 36px; max-width: 500px; line-height: 1.6; opacity: 0.8; }

.tryl-404-btn {
    background: #0d1b0f; color: #fff; padding: 16px 36px; text-decoration: none;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em;
    font-size: 0.85rem; border-radius: 4px; transition: background 0.3s, transform 0.2s;
    display: inline-block;
}
.tryl-404-btn:hover { background: #31d190; color: #0d1b0f; transform: translateY(-2px); }
[data-theme="dark"] .tryl-404-btn { background: #31d190; color: #0d1b0f; }
[data-theme="dark"] .tryl-404-btn:hover { background: #fff; }
[data-theme="mild"] .tryl-404-btn { background: #33322e; color: #fff; }
</style>
<div class="tryl-404-wrap">
    <h1 class="tryl-404-glitch">404</h1>
    <p class="tryl-404-msg">The path you are walking does not exist. Let's get you back to the collection.</p>
    <a href="<?php echo esc_url($shop_url); ?>" class="tryl-404-btn">Return to Shop</a>
</div>
<?php
get_footer(); // This triggers our injected Global Footer