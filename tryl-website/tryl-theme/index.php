<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-text-left">
                <span class="text-righteous">The Righteous</span>
            </div>
            <div class="hero-image-wrapper">
                <img src="<?php echo get_template_directory_uri(); ?>/tryl_portrait.webp" alt="Painterly Portrait" class="hero-portrait">
            </div>
            <div class="hero-text-right">
                <span class="text-yield-life">Yield Life</span>
            </div>
        </div>
    </section>

    <?php wp_footer(); ?>
</body>
</html>
