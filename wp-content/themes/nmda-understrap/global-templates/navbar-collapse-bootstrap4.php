<?php
/**
 * Header Navbar (bootstrap4)
 *
 * @package Understrap
 * @since 1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$container = get_theme_mod( 'understrap_container_type' );
?>

<nav id="main-nav" class="navbar navbar-expand-md navbar-dark" aria-labelledby="main-nav-label">

	<h2 id="main-nav-label" class="screen-reader-text">
		<?php esc_html_e( 'Main Navigation', 'understrap' ); ?>
	</h2>

	<div class="container">

		<div class="row align-items-center">
			<div class="col-md-4 col-6">
				<?php get_template_part( 'global-templates/navbar-branding' ); ?>
			</div>
			<div class="col-md-8 col-6">
			<button
				class="navbar-toggler"
				type="button"
				data-toggle="collapse"
				data-target="#navbarNavDropdown"
				aria-controls="navbarNavDropdown"
				aria-expanded="false"
				aria-label="<?php esc_attr_e( 'Toggle navigation', 'understrap' ); ?>"
			>
				<span class="navbar-toggler-icon"></span>
			</button>

			<!-- The WordPress Menu goes here -->
			<?php
			wp_nav_menu(
				array(
					'theme_location'  => 'member-dashboard',
					'container_class' => 'collapse navbar-collapse',
					'container_id'    => 'navbarNavDropdown',
					'menu_class'      => 'navbar-nav ml-auto',
					'fallback_cb'     => '',
					'menu_id'         => 'main-menu',
					'depth'           => 2,
					'walker'          => new Understrap_WP_Bootstrap_Navwalker(),
				)
			);
			?>
			</div>
			</div>
		</div>


	</div><!-- .container -->

</nav><!-- #main-nav -->
