<?php
/**
 * Tecnoprism – mobile speed boost
 *
 * Paste into WPCode (Code Snippets → Add Snippet → PHP Snippet),
 * set "Insert Method: Auto Insert" and "Location: Frontend Only", then Activate.
 * Afterwards: LiteSpeed Cache → Toolbox → Purge All.
 *
 * To undo: deactivate the snippet and purge LiteSpeed cache again.
 */

/* 1. Hero image: let the phone start downloading it immediately (homepage only). */
add_action( 'wp_head', function () {
	if ( is_front_page() ) {
		echo '<link rel="preload" as="image" fetchpriority="high" href="https://tecnoprism.com/wp-content/uploads/2026/05/Tecnoprism-homepage-1-1-1.webp">' . "\n";
	}
}, 1 );

/* 2. Font: replace the 424 KB Inter .ttf with Google's 48 KB Inter .woff2. */
add_action( 'wp_head', function () {
	$inter = 'https://fonts.gstatic.com/s/inter/v20/UcC73FwrK3iLTeHuS_nVMrMxCp50SjIa1ZL7.woff2';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="preload" as="font" type="font/woff2" href="' . $inter . '" crossorigin>' . "\n";
	echo "<style>@font-face{font-family:'Inter';font-style:normal;font-weight:100 900;font-display:swap;src:url($inter) format('woff2')}</style>\n";
}, 999 );

/* 3. Stop loading stylesheets the site doesn't use. */
add_action( 'wp_print_styles', function () {
	// Font Awesome Pro + Phosphor icon sets (no icons from them on the site).
	wp_dequeue_style( 'font-awesome-pro' );
	wp_dequeue_style( 'pxl-phosphor' );
	// Google Fonts request for 8 font families; Inter is served by step 2,
	// and the theme already bundles the other families itself.
	wp_dequeue_style( 'pxl-google-fonts' );

	// Leftover demo templates whose CSS loads on the homepage but never appears there.
	if ( is_front_page() ) {
		$unused = array(
			394, 447, 2113, 2141, 2458, 2464, 2465, 2776, 2789, 2798, 2800, 2803,
			2865, 2895, 4866, 4883, 5946, 6386, 6995, 7010, 13169, 13171, 14223,
			14381, 14408, 14569, 14570, 14571, 14572, 14573, 15632, 15634, 15636,
			15638, 15640, 16249, 16251, 16291, 16304, 16782, 16820, 16821, 16822,
			17072, 17074,
		);
		foreach ( $unused as $id ) {
			wp_dequeue_style( 'elementor-post-' . $id );
		}
	}
}, 999 );
