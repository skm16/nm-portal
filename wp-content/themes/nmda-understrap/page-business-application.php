<?php
/**
 * Template Name: Business Application Form
 * Template for new member business application
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="wrapper" id="page-wrapper">
    <div class="container" id="content">
        <div class="row">
            <div class="col-md-12 content-area" id="primary">
                <main class="site-main" id="main">

                    <?php while ( have_posts() ) : the_post(); ?>

                        <article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

                            <header class="entry-header">
                                <h1 class="entry-title"><?php the_title(); ?></h1>
                                <p class="lead">Join the New Mexico Logo Program</p>
                            </header>

                            <div class="entry-content">
                                <?php the_content(); ?>

                                <?php if ( is_user_logged_in() ) : ?>
                                    <?php nmda_business_application_form(); ?>
                                <?php else : ?>
                                    <div class="alert alert-info">
                                        <h4>Account Required</h4>
                                        <p>You must be logged in to submit a business application.</p>
                                        <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn btn-primary">Log In</a>
                                        <a href="<?php echo wp_registration_url(); ?>" class="btn btn-secondary">Create Account</a>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </article>

                    <?php endwhile; ?>

                </main>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
