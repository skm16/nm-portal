<?php
/**
 * NMDA Application Forms
 * Handles business application form rendering and submission
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Render business application form
 */
function nmda_business_application_form() {
    // Check for saved draft
    $user_id = get_current_user_id();
    $saved_draft = get_user_meta( $user_id, 'nmda_application_draft', true );
    $draft_updated = get_user_meta( $user_id, 'nmda_application_draft_updated', true );

    ?>

    <?php if ( ! empty( $saved_draft ) ) : ?>
        <div class="alert alert-info alert-dismissible fade show" id="draft-notification">
            <h5><i class="fa fa-clock-o"></i> Saved Draft Found</h5>
            <p>You have a saved draft from <?php echo human_time_diff( strtotime( $draft_updated ), current_time( 'timestamp' ) ); ?> ago.</p>
            <button type="button" class="btn btn-sm btn-primary" id="restore-draft-btn">
                <i class="fa fa-undo"></i> Restore Draft
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-draft-btn">
                <i class="fa fa-trash"></i> Clear Draft & Start Fresh
            </button>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <script>
        var savedDraftData = <?php echo json_encode( $saved_draft ); ?>;
        </script>
    <?php endif; ?>

    <form id="nmda-business-application" class="nmda-reimbursement-form nmda-ajax-form" method="post" enctype="multipart/form-data">

        <?php wp_nonce_field( 'nmda_business_application', 'nmda_business_nonce' ); ?>
        <input type="hidden" name="action" value="nmda_submit_business_application" />

        <!-- Progress Indicator -->
        <ul class="nmda-progress-indicator">
            <li class="nmda-progress-step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-label">Personal Info</span>
            </li>
            <li class="nmda-progress-step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">Business Info</span>
            </li>
            <li class="nmda-progress-step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">Classification</span>
            </li>
            <li class="nmda-progress-step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-label">Products</span>
            </li>
            <li class="nmda-progress-step" data-step="5">
                <span class="step-number">5</span>
                <span class="step-label">Review</span>
            </li>
        </ul>

        <!-- Step 1: Personal Contact Information -->
        <div class="nmda-form-step active" data-step="1">
            <h3>Section 1: Personal Contact Information</h3>
            <p class="text-muted">This information will <strong>NOT</strong> be posted on the website.</p>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="owner_first_name">Responsible Officer/Owner First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="owner_first_name" name="owner_first_name" required />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="owner_last_name">Responsible Officer/Owner Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="owner_last_name" name="owner_last_name" required />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_primary_contact" name="is_primary_contact" value="1" checked />
                    <label class="form-check-label" for="is_primary_contact">
                        Is this person the primary contact for Logo Program matters?
                    </label>
                </div>
            </div>

            <div id="primary-contact-fields" style="display: none;">
                <h4>Primary Contact Person</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_first_name">First Name</label>
                            <input type="text" class="form-control" id="contact_first_name" name="contact_first_name" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_last_name">Last Name</label>
                            <input type="text" class="form-control" id="contact_last_name" name="contact_last_name" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="contact_phone">Best Contact Phone Number <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="505-555-5555" required />
                <small class="form-text text-muted">Format: 505-555-5555</small>
            </div>

            <div class="form-group">
                <label for="contact_email">Contact Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="contact_email" name="contact_email" required />
                <small class="form-text text-muted">This will be used for all correspondence from NMDA</small>
            </div>

            <div class="form-group">
                <label for="contact_address">Mailing Address <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="contact_address" name="contact_address" required />
            </div>

            <div class="form-group">
                <label for="contact_address_2">Mailing Address Line 2</label>
                <input type="text" class="form-control" id="contact_address_2" name="contact_address_2" />
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="contact_city">City <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_city" name="contact_city" required />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="contact_state">State <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_state" name="contact_state" value="NM" required />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="contact_zip">Zip Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_zip" name="contact_zip" required />
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Business Information -->
        <div class="nmda-form-step" data-step="2">
            <h3>Section 2: Business Information</h3>
            <p class="text-muted">This information <strong>WILL</strong> be posted on the Logo Program website.</p>

            <div class="form-group">
                <label for="business_name">Business Legal Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="business_name" name="business_name" required />
            </div>

            <div class="form-group">
                <label for="dba">DBA (Doing Business As)</label>
                <input type="text" class="form-control" id="dba" name="dba" />
                <small class="form-text text-muted">If applicable</small>
            </div>

            <h4>Primary Business Address</h4>
            <div class="form-group">
                <label for="primary_address">Business Physical Address <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="primary_address" name="primary_address" required />
                <small class="form-text text-muted">If open to the public, this is the primary address displayed on the website</small>
            </div>

            <div class="form-group">
                <label for="primary_address_2">Address Line 2</label>
                <input type="text" class="form-control" id="primary_address_2" name="primary_address_2" />
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="primary_city">City <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="primary_city" name="primary_city" required />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="primary_state">State <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="primary_state" name="primary_state" value="NM" required />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="primary_zip">Zip Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="primary_zip" name="primary_zip" required />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="primary_address_type">Address Type <span class="text-danger">*</span></label>
                <select class="form-control" id="primary_address_type" name="primary_address_type" required>
                    <option value="public_hours">Open to the public during regular business hours</option>
                    <option value="public_reservation">Open to the public with a reservation</option>
                    <option value="not_public">Not open to the public (doesn't appear on the website)</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group" id="reservation_instructions_group" style="display: none;">
                <label for="reservation_instructions">Reservation Instructions</label>
                <textarea class="form-control" id="reservation_instructions" name="reservation_instructions" rows="3" placeholder="Call 505-555-5555 or email info@business.com"></textarea>
            </div>

            <div class="form-group" id="other_instructions_group" style="display: none;">
                <label for="other_instructions">Other Instructions</label>
                <textarea class="form-control" id="other_instructions" name="other_instructions" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="business_phone">Business Phone <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="business_phone" name="business_phone" placeholder="505-555-5555" required />
            </div>

            <div class="form-group">
                <label for="business_email">Business Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="business_email" name="business_email" required />
            </div>

            <div class="form-group">
                <label for="website">Website</label>
                <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com" />
            </div>

            <h4>Social Media</h4>
            <p class="text-muted">Select each one that applies and include handles</p>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="has_facebook" />
                        <label class="form-check-label" for="has_facebook">Facebook</label>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="form-group">
                        <input type="text" class="form-control" id="facebook" name="facebook" placeholder="@nmtastethetradition" disabled />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="has_instagram" />
                        <label class="form-check-label" for="has_instagram">Instagram</label>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="form-group">
                        <input type="text" class="form-control" id="instagram" name="instagram" placeholder="@nmtastethetradition" disabled />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="has_twitter" />
                        <label class="form-check-label" for="has_twitter">Twitter/X</label>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="form-group">
                        <input type="text" class="form-control" id="twitter" name="twitter" placeholder="@TasteNewMexico" disabled />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="business_profile">Business Profile <span class="text-danger">*</span></label>
                <textarea class="form-control" id="business_profile" name="business_profile" rows="5" required></textarea>
                <small class="form-text text-muted">Provide a brief description of your business</small>
            </div>

            <div class="form-group">
                <label for="business_hours">Business Hours</label>
                <textarea class="form-control" id="business_hours" name="business_hours" rows="4" placeholder="Monday-Friday: 8:00 AM - 5:00 PM&#10;Saturday: 9:00 AM - 4:00 PM&#10;Sunday: Closed"></textarea>
                <small class="form-text text-muted">If by appointment only, include contact info</small>
            </div>
        </div>

        <!-- Step 3: Classification -->
        <div class="nmda-form-step" data-step="3">
            <h3>Section 3: Logo Program Classification</h3>
            <p class="text-muted">Select all classifications that apply to your business <span class="text-danger">*</span></p>

            <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input classification-check" id="class_grown" name="classification[]" value="grown" />
                <label class="form-check-label" for="class_grown">
                    <strong>NEW MEXICO – Grown with Tradition®</strong><br>
                    <small>For farmers and ranchers who grow, raise and/or handle produce, nuts, livestock & poultry, meat products, horticultural products, and other crops</small>
                </label>
            </div>

            <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input classification-check" id="class_taste" name="classification[]" value="taste" />
                <label class="form-check-label" for="class_taste">
                    <strong>NEW MEXICO – Taste the Tradition®</strong><br>
                    <small>For manufacturers of food and beverage products which are 51% processed, manufactured or made in New Mexico. Includes sauces, seasonings, dried foods, animal products, beverages, baked goods, sweets and other foods</small>
                </label>
            </div>

            <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input classification-check" id="class_associate" name="classification[]" value="associate" />
                <label class="form-check-label" for="class_associate">
                    <strong>NEW MEXICO – Grown/Taste Associate Member</strong><br>
                    <small>For retailers, farmers markets, restaurants, agritourism operations, artisan products, pet food manufacturers, and organizations that support New Mexico agriculture</small>
                </label>
            </div>

            <div id="associate-type-fields" style="display: none;">
                <h4>Associate Member Type</h4>
                <p>Please select all that apply:</p>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assoc_in_person" name="associate_type[]" value="in_person" />
                    <label class="form-check-label" for="assoc_in_person">In-Person Retail (sells 3+ Taste/Grown products)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assoc_online" name="associate_type[]" value="online" />
                    <label class="form-check-label" for="assoc_online">Online Retail</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assoc_restaurant" name="associate_type[]" value="restaurant" />
                    <label class="form-check-label" for="assoc_restaurant">Restaurant (serves NM ingredients)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assoc_tourism" name="associate_type[]" value="tourism" />
                    <label class="form-check-label" for="assoc_tourism">Agritourism Operation</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assoc_artisan" name="associate_type[]" value="artisan" />
                    <label class="form-check-label" for="assoc_artisan">Artisan/Crafted Products</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assoc_pet" name="associate_type[]" value="pet" />
                    <label class="form-check-label" for="assoc_pet">Pet Food Manufacturer</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="assoc_other" name="associate_type[]" value="other" />
                    <label class="form-check-label" for="assoc_other">Other</label>
                </div>

                <div class="form-group mt-3" id="assoc_other_text_group" style="display: none;">
                    <label for="associate_other_text">Please describe</label>
                    <input type="text" class="form-control" id="associate_other_text" name="associate_other_text" />
                </div>
            </div>

            <div class="form-group mt-4">
                <label for="num_employees">Number of Employees</label>
                <input type="number" class="form-control" id="num_employees" name="num_employees" min="0" />
                <small class="form-text text-muted">Approximate number (for NMDA records)</small>
            </div>

            <div class="form-group">
                <label>Type of Sales</label>
                <p class="text-muted">Select all that apply</p>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="sales_local" name="sales_type[]" value="local" />
                    <label class="form-check-label" for="sales_local">Local</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="sales_regional" name="sales_type[]" value="regional" />
                    <label class="form-check-label" for="sales_regional">Regional</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="sales_in_state" name="sales_type[]" value="in_state" />
                    <label class="form-check-label" for="sales_in_state">In-State</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="sales_national" name="sales_type[]" value="national" />
                    <label class="form-check-label" for="sales_national">National</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="sales_international" name="sales_type[]" value="international" />
                    <label class="form-check-label" for="sales_international">International</label>
                </div>
            </div>
        </div>

        <!-- Step 4: Products -->
        <div class="nmda-form-step" data-step="4">
            <h3>Section 4: Products</h3>
            <p class="text-muted">Select all product types that apply to your business</p>

            <?php nmda_render_product_selection(); ?>
        </div>

        <!-- Step 5: Review & Submit -->
        <div class="nmda-form-step" data-step="5">
            <h3>Review Your Application</h3>
            <p>Please review your information before submitting. You can go back to any step to make changes.</p>

            <div id="application-summary" class="alert alert-secondary">
                <p><strong>Application Summary</strong></p>
                <p>Your application details will appear here.</p>
            </div>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="terms_agree" name="terms_agree" required />
                <label class="form-check-label" for="terms_agree">
                    I agree to the <a href="/logo-program-guidelines/" target="_blank">Logo Program Guidelines</a> and certify that the information provided is accurate <span class="text-danger">*</span>
                </label>
            </div>
        </div>

        <!-- Form Navigation -->
        <div class="nmda-form-navigation">
            <button type="button" class="btn btn-secondary nmda-btn-prev" style="display: none;">
                <i class="fa fa-arrow-left"></i> Previous
            </button>
            <button type="button" class="btn btn-outline-secondary nmda-btn-save-draft">
                <i class="fa fa-save"></i> Save Draft
            </button>
            <button type="button" class="btn btn-primary nmda-btn-next">
                Next <i class="fa fa-arrow-right"></i>
            </button>
            <button type="submit" class="btn btn-success nmda-btn-submit" style="display: none;">
                <i class="fa fa-check"></i> Submit Application
            </button>
        </div>
    </form>
    <?php
}

/**
 * Render product selection interface
 */
function nmda_render_product_selection() {
    $categories = nmda_get_product_categories();

    if ( empty( $categories ) ) {
        echo '<div class="alert alert-warning">';
        echo '<strong>Note:</strong> Product categories are being set up. If you just activated the theme, please refresh this page.';
        echo '</div>';
        return;
    }

    echo '<div class="nmda-product-selection">';
    echo '<p class="mb-3">Select all product types that apply to your business. You can select multiple products.</p>';

    foreach ( $categories as $category ) {
        $products = nmda_get_products_by_category( $category->term_id );

        if ( empty( $products ) ) {
            continue;
        }

        echo '<div class="product-category card mb-3">';
        echo '<div class="card-header">';
        echo '<h5 class="mb-0">';
        echo '<button class="btn btn-link text-left w-100" type="button" data-toggle="collapse" data-target="#category_' . esc_attr( $category->term_id ) . '" aria-expanded="false">';
        echo '<i class="fa fa-chevron-down"></i> ' . esc_html( $category->name );
        if ( $category->description ) {
            echo ' <small class="text-muted">(' . esc_html( $category->description ) . ')</small>';
        }
        echo '</button>';
        echo '</h5>';
        echo '</div>';

        echo '<div id="category_' . esc_attr( $category->term_id ) . '" class="collapse">';
        echo '<div class="card-body">';

        foreach ( $products as $product ) {
            echo '<div class="form-check">';
            echo '<input type="checkbox" class="form-check-input" id="product_' . esc_attr( $product->term_id ) . '" name="products[]" value="' . esc_attr( $product->term_id ) . '" />';
            echo '<label class="form-check-label" for="product_' . esc_attr( $product->term_id ) . '">' . esc_html( $product->name ) . '</label>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
}

/**
 * Handle business application submission
 */
function nmda_handle_business_application_submission() {
    // Verify nonce
    if ( ! isset( $_POST['nmda_business_nonce'] ) || ! wp_verify_nonce( $_POST['nmda_business_nonce'], 'nmda_business_application' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }

    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in to submit an application.' ) );
    }

    $user_id = get_current_user_id();

    // Create business post
    $business_data = array(
        'post_title'   => sanitize_text_field( $_POST['business_name'] ),
        'post_type'    => 'nmda_business',
        'post_status'  => 'pending',
        'post_author'  => $user_id,
    );

    $business_id = wp_insert_post( $business_data );

    if ( is_wp_error( $business_id ) ) {
        wp_send_json_error( array( 'message' => 'Failed to create business. Please try again.' ) );
    }

    // Save ACF fields
    if ( function_exists( 'update_field' ) ) {
        $acf_fields = array(
            'dba', 'business_phone', 'business_email', 'website', 'business_profile', 'business_hours',
            'num_employees', 'owner_first_name', 'owner_last_name', 'is_primary_contact',
            'contact_first_name', 'contact_last_name', 'contact_phone', 'contact_email',
            'contact_address', 'contact_address_2', 'contact_city', 'contact_state', 'contact_zip',
            'primary_address', 'primary_address_2', 'primary_city', 'primary_state', 'primary_zip',
            'primary_address_type', 'reservation_instructions', 'other_instructions',
            'facebook', 'instagram', 'twitter', 'classification', 'associate_type', 'associate_other_text',
            'sales_type', 'approval_status',
        );

        foreach ( $acf_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_field( $field, $_POST[ $field ], $business_id );
            }
        }

        // Set approval status
        update_field( 'approval_status', 'pending', $business_id );
    }

    // Assign product taxonomy terms
    if ( isset( $_POST['products'] ) && is_array( $_POST['products'] ) ) {
        $product_ids = array_map( 'intval', $_POST['products'] );
        wp_set_object_terms( $business_id, $product_ids, 'product_type' );
    }

    // Associate user with business
    nmda_add_user_to_business( $user_id, $business_id, 'owner', null );

    // Send confirmation email
    $user = get_userdata( $user_id );
    $subject = 'Business Application Received - NMDA Logo Program';
    $message = sprintf(
        "Dear %s,\n\nThank you for applying to the New Mexico Logo Program.\n\nYour application for %s has been received and is under review. You will be notified once your application has been processed.\n\nApplication ID: #%d\n\nYou can check the status of your application by logging into your member dashboard.\n\nThank you,\nNew Mexico Department of Agriculture",
        $user->display_name,
        $_POST['business_name'],
        $business_id
    );

    wp_mail( $user->user_email, $subject, $message );

    // Send admin notification
    $admin_email = get_option( 'admin_email' );
    $admin_subject = 'New Business Application Submitted';
    $admin_message = sprintf(
        "A new business application has been submitted.\n\nBusiness: %s\nSubmitted by: %s (%s)\n\nReview application: %s",
        $_POST['business_name'],
        $user->display_name,
        $user->user_email,
        admin_url( 'post.php?post=' . $business_id . '&action=edit' )
    );

    wp_mail( $admin_email, $admin_subject, $admin_message );

    // Clear any saved draft after successful submission
    delete_user_meta( $user_id, 'nmda_application_draft' );
    delete_user_meta( $user_id, 'nmda_application_draft_updated' );

    wp_send_json_success( array(
        'message'     => 'Application submitted successfully!',
        'business_id' => $business_id,
        'redirect'    => home_url( '/dashboard' ),
    ) );
}
add_action( 'wp_ajax_nmda_submit_business_application', 'nmda_handle_business_application_submission' );

/**
 * Handle draft save
 */
function nmda_handle_draft_save() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nmda-ajax-nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }

    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in to save a draft.' ) );
    }

    $user_id = get_current_user_id();

    // Parse form data
    parse_str( $_POST['formData'], $form_data );

    // Save draft as user meta
    update_user_meta( $user_id, 'nmda_application_draft', $form_data );
    update_user_meta( $user_id, 'nmda_application_draft_updated', current_time( 'mysql' ) );

    wp_send_json_success( array( 'message' => 'Draft saved successfully!' ) );
}
add_action( 'wp_ajax_nmda_save_draft', 'nmda_handle_draft_save' );

/**
 * Handle draft clear
 */
function nmda_handle_draft_clear() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nmda-ajax-nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ) );
    }

    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
    }

    $user_id = get_current_user_id();

    // Delete draft user meta
    delete_user_meta( $user_id, 'nmda_application_draft' );
    delete_user_meta( $user_id, 'nmda_application_draft_updated' );

    wp_send_json_success( array( 'message' => 'Draft cleared successfully!' ) );
}
add_action( 'wp_ajax_nmda_clear_draft', 'nmda_handle_draft_clear' );
