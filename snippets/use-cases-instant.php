<?php
/**
 * Tecnoprism – use cases page: show the cards instantly
 *
 * Before: the page loaded empty, then the browser downloaded all use cases from
 * the WordPress API (780 KB, ~2 s on the server) and built the cards.
 * After: the cards are already in the page HTML; filters and search work the same.
 *
 * Paste into WPCode as a separate PHP Snippet,
 * Insert Method: Auto Insert, Location: Run Everywhere, Device Type: Any Device Type.
 * Afterwards: LiteSpeed Cache → Toolbox → Purge All.
 * No Elementor changes needed. To undo: deactivate the snippet and purge the cache.
 */

add_filter( 'elementor/widget/render_content', function ( $content, $widget ) {
	if ( 'html' !== $widget->get_name() || false === strpos( $content, 'id="tp-usecases"' ) ) {
		return $content;
	}

	$tax   = 'use-case-category';
	$posts = get_posts( array(
		'post_type'        => 'use-cases',
		'posts_per_page'   => 100,
		'suppress_filters' => false,
	) );
	if ( ! $posts ) {
		return $content;
	}

	$cards = '';
	foreach ( $posts as $p ) {
		$link  = esc_url( get_permalink( $p ) );
		$title = get_the_title( $p );
		$terms = get_the_terms( $p, $tax );
		$terms = is_array( $terms ) ? $terms : array();
		$img   = has_post_thumbnail( $p )
			? get_the_post_thumbnail( $p, 'medium_large', array( 'alt' => wp_strip_all_tags( $title ), 'loading' => 'lazy' ) )
			: '<div class="tp-uc__noimg"></div>';
		$cat   = $terms ? '<span class="tp-uc__cat">' . esc_html( $terms[0]->name ) . '</span>' : '';

		$cards .= '<article class="tp-uc__card" data-cats="' . esc_attr( implode( ' ', wp_list_pluck( $terms, 'term_id' ) ) ) . '" data-search="' . esc_attr( mb_strtolower( wp_strip_all_tags( $title ) ) ) . '">'
			. '<a class="tp-uc__media" href="' . $link . '">' . $img . '</a>'
			. '<div class="tp-uc__body">' . $cat
			. '<h3 class="tp-uc__title"><a href="' . $link . '">' . $title . '</a></h3>'
			. '<a class="tp-uc__more" href="' . $link . '">Read more <span>→</span></a>'
			. '</div></article>';
	}

	$pills = '<button class="tp-uc__pill is-active" data-term="all">All</button>';
	$all   = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
	foreach ( is_array( $all ) ? $all : array() as $t ) {
		$pills .= '<button class="tp-uc__pill" data-term="' . (int) $t->term_id . '">' . esc_html( $t->name ) . '</button>';
	}

	$new = preg_replace( '#(<div[^>]*id="tp-uc-grid"[^>]*>)\s*(</div>)#', '$1' . str_replace( array( '\\', '$' ), array( '\\\\', '\$' ), $cards ) . '$2', $content, 1, $done );
	if ( ! $done ) {
		return $content; // Markup not as expected: leave the page exactly as it was.
	}
	$new = preg_replace( '#(<span[^>]*tp-uc__filters-label[^>]*>.*?</span>)#s', '$1' . $pills, $new, 1 );
	$new = preg_replace( '#<div[^>]*id="tp-uc-loading"[^>]*>.*?</div>#s', '', $new, 1 );
	// Remove the old script that downloaded everything from the API.
	$new = preg_replace( '#<script\b[^>]*>(?:(?!</script>).)*tp-usecases(?:(?!</script>).)*</script>#s', '', $new );

	$new .= <<<'JS'
<script>
(function(){var R=document.getElementById('tp-usecases');if(!R)return;
var cards=[].slice.call(R.querySelectorAll('.tp-uc__card')),pills=[].slice.call(R.querySelectorAll('.tp-uc__pill')),
s=R.querySelector('#tp-uc-search'),b=R.querySelector('#tp-uc-search-btn'),empty=R.querySelector('#tp-uc-empty'),term='all',q='',t;
function render(){var n=0;cards.forEach(function(c){var ok=(term==='all'||(' '+c.getAttribute('data-cats')+' ').indexOf(' '+term+' ')>-1)&&(!q||c.getAttribute('data-search').indexOf(q)>-1);c.style.display=ok?'':'none';if(ok)n++});if(empty)empty.style.display=n?'none':'block'}
pills.forEach(function(p){p.addEventListener('click',function(){pills.forEach(function(x){x.classList.remove('is-active')});p.classList.add('is-active');term=p.getAttribute('data-term');render()})});
if(s){s.addEventListener('input',function(){clearTimeout(t);t=setTimeout(function(){q=s.value.trim().toLowerCase();render()},200)});
if(b)b.addEventListener('click',function(){q=s.value.trim().toLowerCase();render()})}})();
</script>
JS;
	return $new;
}, 10, 2 );

/* When a use case is added or edited, refresh the cached page so the new card shows up. */
add_action( 'save_post_use-cases', function () {
	delete_post_meta_by_key( '_elementor_element_cache' );
	do_action( 'litespeed_purge_all' );
} );
