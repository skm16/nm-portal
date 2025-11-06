<?php
/**
 * NMDA API Integrations (Email Services, etc.)
 *
 * @package NMDA_Understrap_Child
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Email Integration Class
 * Supports Mailchimp and ActiveCampaign
 */
class NMDA_Email_Integration {

    /**
     * API credentials
     *
     * @var string
     */
    private $api_key;
    private $list_id;
    private $service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->service = get_option( 'nmda_email_service', 'mailchimp' );
        $this->api_key = get_option( 'nmda_email_api_key', '' );
        $this->list_id = get_option( 'nmda_email_list_id', '' );
    }

    /**
     * Sync member to email service
     *
     * @param int $user_id User ID.
     * @param int $business_id Business post ID.
     * @param string $action Action: 'add', 'update', 'remove'.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function sync_member( $user_id, $business_id, $action = 'add' ) {
        if ( empty( $this->api_key ) || empty( $this->list_id ) ) {
            return new WP_Error( 'missing_credentials', __( 'Email service credentials not configured.', 'nmda-understrap' ) );
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new WP_Error( 'invalid_user', __( 'Invalid user ID.', 'nmda-understrap' ) );
        }

        $business = get_post( $business_id );
        if ( ! $business ) {
            return new WP_Error( 'invalid_business', __( 'Invalid business ID.', 'nmda-understrap' ) );
        }

        // Prepare member data
        $member_data = $this->prepare_member_data( $user, $business );

        // Route to appropriate service
        if ( $this->service === 'mailchimp' ) {
            return $this->sync_mailchimp( $member_data, $action );
        } elseif ( $this->service === 'activecampaign' ) {
            return $this->sync_activecampaign( $member_data, $action );
        }

        return new WP_Error( 'invalid_service', __( 'Invalid email service selected.', 'nmda-understrap' ) );
    }

    /**
     * Prepare member data for sync
     *
     * @param WP_User $user User object.
     * @param WP_Post $business Business post object.
     * @return array Member data array.
     */
    private function prepare_member_data( $user, $business ) {
        // Get business categories
        $categories = wp_get_post_terms( $business->ID, 'business_category', array( 'fields' => 'names' ) );

        // Get business location
        $addresses = nmda_get_business_addresses( $business->ID );
        $primary_address = null;
        foreach ( $addresses as $address ) {
            if ( $address->is_primary ) {
                $primary_address = $address;
                break;
            }
        }

        return array(
            'email'         => $user->user_email,
            'first_name'    => $user->first_name,
            'last_name'     => $user->last_name,
            'business_name' => $business->post_title,
            'business_id'   => $business->ID,
            'categories'    => $categories,
            'city'          => $primary_address->city ?? '',
            'state'         => $primary_address->state ?? '',
            'zip'           => $primary_address->zip_code ?? '',
            'phone'         => get_user_meta( $user->ID, 'phone', true ),
        );
    }

    /**
     * Sync with Mailchimp
     *
     * @param array $member_data Member data.
     * @param string $action Action.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function sync_mailchimp( $member_data, $action ) {
        // Extract API key parts (format: key-dc)
        $parts = explode( '-', $this->api_key );
        if ( count( $parts ) !== 2 ) {
            return new WP_Error( 'invalid_api_key', __( 'Invalid Mailchimp API key format.', 'nmda-understrap' ) );
        }

        $datacenter = $parts[1];
        $api_url = "https://{$datacenter}.api.mailchimp.com/3.0";

        $subscriber_hash = md5( strtolower( $member_data['email'] ) );

        if ( $action === 'remove' ) {
            // Remove from list
            $endpoint = "{$api_url}/lists/{$this->list_id}/members/{$subscriber_hash}";
            $response = wp_remote_request( $endpoint, array(
                'method'  => 'DELETE',
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key ),
                ),
            ) );
        } else {
            // Add or update
            $endpoint = "{$api_url}/lists/{$this->list_id}/members/{$subscriber_hash}";

            $body = array(
                'email_address' => $member_data['email'],
                'status'        => 'subscribed',
                'merge_fields'  => array(
                    'FNAME'    => $member_data['first_name'],
                    'LNAME'    => $member_data['last_name'],
                    'PHONE'    => $member_data['phone'],
                    'MMERGE5'  => $member_data['business_name'], // Custom field - adjust as needed
                ),
                'tags'          => $member_data['categories'],
            );

            $response = wp_remote_request( $endpoint, array(
                'method'  => 'PUT',
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key ),
                    'Content-Type'  => 'application/json',
                ),
                'body'    => json_encode( $body ),
            ) );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 300 ) {
            return true;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return new WP_Error( 'mailchimp_error', $body['detail'] ?? __( 'Unknown Mailchimp error.', 'nmda-understrap' ) );
    }

    /**
     * Sync with ActiveCampaign
     *
     * @param array $member_data Member data.
     * @param string $action Action.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function sync_activecampaign( $member_data, $action ) {
        $api_url = get_option( 'nmda_activecampaign_url', '' );
        if ( empty( $api_url ) ) {
            return new WP_Error( 'missing_url', __( 'ActiveCampaign URL not configured.', 'nmda-understrap' ) );
        }

        $api_url = trailingslashit( $api_url ) . 'api/3';

        if ( $action === 'remove' ) {
            // First, get contact ID
            $search_url = add_query_arg( 'email', $member_data['email'], "{$api_url}/contacts" );
            $response = wp_remote_get( $search_url, array(
                'headers' => array(
                    'Api-Token' => $this->api_key,
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['contacts'][0]['id'] ) ) {
                $contact_id = $body['contacts'][0]['id'];
                $delete_response = wp_remote_request( "{$api_url}/contacts/{$contact_id}", array(
                    'method'  => 'DELETE',
                    'headers' => array(
                        'Api-Token' => $this->api_key,
                    ),
                ) );

                return ! is_wp_error( $delete_response );
            }

            return true;
        } else {
            // Add or update contact
            $contact_data = array(
                'contact' => array(
                    'email'     => $member_data['email'],
                    'firstName' => $member_data['first_name'],
                    'lastName'  => $member_data['last_name'],
                    'phone'     => $member_data['phone'],
                    'fieldValues' => array(
                        array(
                            'field' => '1', // Business name field - adjust ID as needed
                            'value' => $member_data['business_name'],
                        ),
                    ),
                ),
            );

            $response = wp_remote_post( "{$api_url}/contact/sync", array(
                'headers' => array(
                    'Api-Token'    => $this->api_key,
                    'Content-Type' => 'application/json',
                ),
                'body'    => json_encode( $contact_data ),
            ) );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );
            return $code >= 200 && $code < 300;
        }
    }

    /**
     * Bulk sync all approved members
     *
     * @return array Results array with success/error counts.
     */
    public function bulk_sync() {
        $results = array(
            'success' => 0,
            'errors'  => 0,
        );

        // Get all published businesses
        $businesses = get_posts( array(
            'post_type'      => 'nmda_business',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ) );

        foreach ( $businesses as $business ) {
            // Get all active users for this business
            $users = nmda_get_business_users( $business->ID, 'active' );

            foreach ( $users as $user_business ) {
                $result = $this->sync_member( $user_business->user_id, $business->ID, 'add' );

                if ( is_wp_error( $result ) ) {
                    $results['errors']++;
                } else {
                    $results['success']++;
                }
            }
        }

        return $results;
    }
}

/**
 * Get email integration instance
 *
 * @return NMDA_Email_Integration
 */
function nmda_get_email_integration() {
    static $instance = null;
    if ( null === $instance ) {
        $instance = new NMDA_Email_Integration();
    }
    return $instance;
}

/**
 * Sync member when business is published
 *
 * @param string $new_status New post status.
 * @param string $old_status Old post status.
 * @param WP_Post $post Post object.
 */
function nmda_sync_on_business_publish( $new_status, $old_status, $post ) {
    if ( $post->post_type !== 'nmda_business' ) {
        return;
    }

    if ( $new_status === 'publish' && $old_status !== 'publish' ) {
        // Business newly published - sync all users
        $users = nmda_get_business_users( $post->ID, 'active' );
        $integration = nmda_get_email_integration();

        foreach ( $users as $user_business ) {
            $integration->sync_member( $user_business->user_id, $post->ID, 'add' );
        }
    }
}
add_action( 'transition_post_status', 'nmda_sync_on_business_publish', 10, 3 );

/**
 * Sync member when user is added to business
 *
 * Note: This should be called after nmda_add_user_to_business()
 *
 * @param int $user_id User ID.
 * @param int $business_id Business post ID.
 */
function nmda_sync_on_user_added( $user_id, $business_id ) {
    $post_status = get_post_status( $business_id );
    if ( $post_status === 'publish' ) {
        $integration = nmda_get_email_integration();
        $integration->sync_member( $user_id, $business_id, 'add' );
    }
}
