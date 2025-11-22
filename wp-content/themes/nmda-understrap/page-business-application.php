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

<div class="wrapper dashboard-wrapper" id="page-wrapper">
    <div class="container" id="content">

        <!-- Dashboard Header -->
        <div class="dashboard-header">
        
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="dashboard-title"><i class="fa fa-file-alt"></i> Business Application</h1>
                        <p class="dashboard-subtitle">Join the New Mexico Logo Program</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            
        </div>

        <!-- Main Content -->
        <div class="mt-4">
            <div class="row">
                <div class="col-12">

                    <?php while ( have_posts() ) : the_post(); ?>

                        <?php if ( is_user_logged_in() ) : ?>

                            <!-- Page Content (if any) -->
                            <?php if ( get_the_content() ) : ?>
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <?php the_content(); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Application Form -->
                            <div class="card">
                                <div class="card-body">
                                    <?php nmda_business_application_form(); ?>
                                </div>
                            </div>

                        <?php else : ?>

                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fa fa-lock fa-3x text-muted mb-3"></i>
                                    <h4>Account Required</h4>
                                    <p>You must be logged in to submit a business application.</p>
                                    <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn btn-primary">
                                        <i class="fa fa-sign-in-alt"></i> Log In
                                    </a>
                                    <a href="<?php echo home_url('/register/'); ?>" class="btn btn-secondary">
                                        <i class="fa fa-user-plus"></i> Create Account
                                    </a>
                                </div>
                            </div>

                        <?php endif; ?>

                    <?php endwhile; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
