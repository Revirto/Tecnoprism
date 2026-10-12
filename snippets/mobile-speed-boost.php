<?php
/**
 * Tecnoprism – mobile speed boost (v2)
 *
 * Paste into WPCode (Code Snippets → Add Snippet → PHP Snippet),
 * Insert Method: Auto Insert, Location: Frontend Only, Device Type: Any Device Type.
 * Afterwards: LiteSpeed Cache → Toolbox → Purge All.
 *
 * To undo: deactivate the snippet and purge LiteSpeed cache again.
 */

/* 1. Hero image: start downloading it immediately on phones/tablets (homepage only).
 *    On desktop the first thing painted is the heading, so it is left alone there.
 *    Phones (up to 575px wide) get a cropped 760px-wide copy (43 KB instead of 78 KB) once
 *    "tecnoprism-hero-mobile.webp" is uploaded to the Media Library; until then nothing changes. */
function tp_hero_mobile_url() {
	static $url = null;
	if ( null === $url ) {
		$ids = get_posts( array( 'post_type' => 'attachment', 'name' => 'tecnoprism-hero-mobile', 'posts_per_page' => 1, 'fields' => 'ids' ) );
		$url = $ids ? (string) wp_get_attachment_url( $ids[0] ) : '';
	}
	return $url;
}

add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$desktop = 'https://tecnoprism.com/wp-content/uploads/2026/05/Tecnoprism-homepage-1-1-1.webp';
	$mobile  = tp_hero_mobile_url();
	if ( $mobile ) {
		echo '<link rel="preload" as="image" fetchpriority="high" media="(max-width: 575px)" href="' . esc_url( $mobile ) . '">' . "\n";
		echo '<link rel="preload" as="image" fetchpriority="high" media="(min-width: 576px) and (max-width: 1024px)" href="' . $desktop . '">' . "\n";
		echo '<style>@media (max-width:575px){.elementor-12359 .elementor-element.elementor-element-6751522{background-image:url(' . esc_url( $mobile ) . ')!important}}</style>' . "\n";
	} else {
		echo '<link rel="preload" as="image" fetchpriority="high" media="(max-width: 1024px)" href="' . $desktop . '">' . "\n";
	}
}, 1 );

/* 2. Font: the theme declares Inter as two .ttf files (424 KB + 463 KB).
 *    Serve the theme stylesheet without those two declarations, and load Inter
 *    as Google's small .woff2 files instead (48 KB for normal text). */
add_filter( 'style_loader_src', function ( $src, $handle ) {
	if ( 'pxl-style' !== $handle ) {
		return $src;
	}
	$orig = get_template_directory() . '/assets/css/style.css';
	if ( ! file_exists( $orig ) ) {
		return $src;
	}
	$up   = wp_upload_dir();
	$name = 'style-' . filemtime( $orig ) . '-1.css';
	$file = $up['basedir'] . '/tp-speed/' . $name;
	if ( ! file_exists( $file ) ) {
		$css = file_get_contents( $orig );
		$css = preg_replace( '/@font-face\s*\{[^}]*font-family:\s*[\'"]?Inter[\'"]?\s*;[^}]*\}/', '', $css );
		// The copy lives in /uploads, so point relative links back at the theme folder.
		$base = get_template_directory_uri() . '/assets/css/';
		$css  = preg_replace( '#url\(\s*([\'"]?)(?![\'"]|data:|https?:|/|\#)#i', 'url($1' . $base, $css );
		wp_mkdir_p( dirname( $file ) );
		if ( ! file_put_contents( $file, $css ) ) {
			return $src;
		}
	}
	return $up['baseurl'] . '/tp-speed/' . $name;
}, 10, 2 );

add_action( 'wp_head', function () {
	$g = 'https://fonts.gstatic.com/s/inter/v20/';
	$latin     = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';
	$latin_ext = 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C4,U+2113,U+2C60-2C7F,U+A720-A7FF';
	$faces = array(
		array( 'normal', 'UcC73FwrK3iLTeHuS_nVMrMxCp50SjIa25L7SUc.woff2', $latin_ext ),
		array( 'normal', 'UcC73FwrK3iLTeHuS_nVMrMxCp50SjIa1ZL7.woff2', $latin ),
		array( 'italic', 'UcC53FwrK3iLTcvneQg7Ca725JhhKnNqk6L3UUMJng.woff2', $latin_ext ),
		array( 'italic', 'UcC53FwrK3iLTcvneQg7Ca725JhhKnNqk6L5UUM.woff2', $latin ),
	);
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="preload" as="font" type="font/woff2" href="' . $g . $faces[1][1] . '" crossorigin>' . "\n";
	echo '<style id="tp-inter">';
	foreach ( $faces as $f ) {
		echo "@font-face{font-family:'Inter';font-style:{$f[0]};font-weight:100 900;font-display:swap;src:url({$g}{$f[1]}) format('woff2');unicode-range:{$f[2]}}";
	}
	echo "</style>\n";
}, 2 );

/* 3. Stop loading stylesheets the site doesn't use. */
add_action( 'wp_print_styles', function () {
	// Font Awesome Pro + Phosphor icon sets (no icons from them on the site).
	// Swapped for an empty placeholder, because other theme styles list them as dependencies.
	foreach ( array( 'font-awesome-pro', 'pxl-phosphor' ) as $handle ) {
		wp_deregister_style( $handle );
		wp_register_style( $handle, false );
	}
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

/* 4. Google Tag Manager (added by Site Kit): wait for the visitor's first scroll/tap.
 *    LiteSpeed's "JS Delayed Includes" doesn't catch this snippet, so mark it here.
 *    LiteSpeed's delay loader runs every script of this type on first interaction. */
add_filter( 'wp_inline_script_attributes', function ( $attributes, $javascript ) {
	if ( ! is_admin() && false !== strpos( $javascript, 'googletagmanager.com/gtm.js' ) ) {
		$attributes['type'] = 'litespeed/javascript';
	}
	return $attributes;
}, 10, 2 );

/* 5. Safety net: if the visitor hasn't scrolled or tapped within 5 s of the page
 *    finishing loading, load the delayed scripts anyway (Tag Manager with Apollo and
 *    Salespanel, reCAPTCHA), so visitors who only read are still tracked.
 *    Keep Site Kit's Google tag (googletagmanager.com/gtag/js) OUT of LiteSpeed's
 *    "JS Delayed Includes": CookieYes blocks it until consent, which stops LiteSpeed
 *    from loading anything queued after it (including reCAPTCHA). */
add_action( 'wp_footer', function () {
	echo '<script data-no-optimize="1">(function(){var d=false,ev=window.litespeed_ui_events||["mouseover","click","keydown","wheel","touchmove","touchstart","pointerup","pointerdown"];'
		. 'ev.forEach(function(e){window.addEventListener(e,function(){d=true},{passive:true,once:true})});'
		. 'window.addEventListener("load",function(){setTimeout(function(){if(!d&&typeof litespeed_load_delayed_js_force==="function"){d=true;litespeed_load_delayed_js_force()}},5000)})})();</script>' . "\n";
}, 99 );

/* 6. Homepage only: drop TweenMax (GSAP 2, 37 KB). Nothing on the page uses it, and the
 *    GSAP 3 file the theme also loads provides the same names if anything asks for them. */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_front_page() ) {
		wp_deregister_script( 'pxl-tweenmax' );
		wp_register_script( 'pxl-tweenmax', false );
	}
}, 999 );

/* 7. Oversized logos: serve the small copies WordPress already made.
 *    - Top-bar "Automation CoE" logo: 2048px file shown at 155px (and hidden on phones,
 *      where it still downloaded): use the 300px copy and let hidden copies skip loading.
 *    - Side "AutomationCOE" badge: 2560px file shown at ~270px: use the 768px copy. */
add_filter( 'litespeed_buffer_before', function ( $html ) {
	$html = str_replace(
		'<img src="https://tecnoprism.com/wp-content/uploads/2026/07/automation-coe-logo.webp" alt="Automation CoE" style="width:155px;',
		'<img loading="lazy" src="https://tecnoprism.com/wp-content/uploads/2026/07/automation-coe-logo-300x96.webp" alt="Automation CoE" style="width:155px;',
		$html
	);
	$html = str_replace(
		'uploads/2026/09/AutomationCOE-2-scaled.webp" alt="Automation CoE" width="2560" height="390"',
		'uploads/2026/09/AutomationCOE-2-768x117.webp" alt="Automation CoE" width="768" height="117"',
		$html
	);
	return $html;
} );
