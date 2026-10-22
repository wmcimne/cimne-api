<?php
/**
 * Staff profile template.
 *
 * Reuses the current production profile classes so the existing CIMNE styles
 * continue to control presentation.
 *
 * Expected $args:
 * - person: reduced /persona object.
 * - back_url: translated Directory URL.
 * - placeholder_image: temporary generic profile image.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$person = isset( $args['person'] ) && is_array( $args['person'] )
    ? $args['person']
    : array();

$back_url = isset( $args['back_url'] ) ? (string) $args['back_url'] : '';
$image_url = isset( $args['placeholder_image'] ) ? (string) $args['placeholder_image'] : '';

$full_name = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';
$email = isset( $person['email'] ) ? trim( (string) $person['email'] ) : '';
$phone = isset( $person['phone'] ) ? trim( (string) $person['phone'] ) : '';
$location = isset( $person['location'] ) ? trim( (string) $person['location'] ) : '';
$address_line = isset( $person['address_line'] ) ? trim( (string) $person['address_line'] ) : '';
$address_locality = isset( $person['address_locality'] ) ? trim( (string) $person['address_locality'] ) : '';
$department = isset( $person['department'] ) ? trim( (string) $person['department'] ) : '';
$positions = isset( $person['positions'] ) && is_array( $person['positions'] )
    ? array_values( array_filter( array_map( 'strval', $person['positions'] ) ) )
    : array();
$position_items = isset( $person['position_items'] ) && is_array( $person['position_items'] )
    ? $person['position_items']
    : array();

if ( empty( $position_items ) && ! empty( $positions ) ) {
    foreach ( $positions as $position ) {
        $position_items[] = array(
            'political_role'   => '',
            'role_description' => $position,
        );
    }
}
$scopus_id = isset( $person['scopus_id'] ) ? trim( (string) $person['scopus_id'] ) : '';
$orcid = isset( $person['orcid'] ) ? trim( (string) $person['orcid'] ) : '';
$researcher_id = isset( $person['researcher_id'] ) ? trim( (string) $person['researcher_id'] ) : '';
$google_scholar = isset( $person['google_scholar'] ) ? trim( (string) $person['google_scholar'] ) : '';
$cv = isset( $person['cv'] ) ? trim( (string) $person['cv'] ) : '';

$email_id = function_exists( 'cimne_directory_email_element_id' )
    ? cimne_directory_email_element_id( $email )
    : '';

$scopus_url = cimne_directory_profile_external_url( 'scopus', $scopus_id );
$orcid_url = cimne_directory_profile_external_url( 'orcid', $orcid );
$researcher_url = cimne_directory_profile_external_url( 'researcher_id', $researcher_id );
$google_url = cimne_directory_profile_external_url( 'google_scholar', $google_scholar );

$has_address = '' !== $address_line || '' !== $address_locality;

?>

<div id="staff-profile-container">
    <div class="staff-profile">
        <?php if ( '' !== $back_url ) : ?>
            <div class="staff-ficha-back">
                <div>
                    <a class="back" href="<?php echo esc_url( $back_url ); ?>">
                        <?php echo esc_html__( 'Back', 'cimne' ); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="staff-profile-header">
            <div class="column-left">
                <?php if ( '' !== $image_url ) : ?>
                    <div class="staff-profile-header-image">
                        <img
                            src="<?php echo esc_url( $image_url ); ?>"
                            alt=""
                            class="cimne-directory-profile-placeholder"
                            decoding="async"
                        >
                    </div>
                <?php endif; ?>
            </div>

            <div class="column-right">
                <div class="staff-profile-header-info">
                    <h4 class="staff-profile-header-name"><?php echo esc_html( $full_name ); ?></h4>

                    <?php if ( '' !== $email && '' !== $email_id ) : ?>
                        <p
                            id="<?php echo esc_attr( $email_id ); ?>"
                            class="staff-email copy-email-to-clipboard"
                        ><?php echo esc_html__( 'Copy email', 'cimne' ); ?></p>
                    <?php endif; ?>

                    <?php if ( '' !== $phone ) : ?>
                        <p class="staff-profile-header-tlf"><?php echo esc_html( $phone ); ?></p>
                    <?php endif; ?>

                    <?php if ( '' !== $location ) : ?>
                        <h5 class="staff-profile-header-branch"><?php echo esc_html__( 'Location', 'cimne' ); ?></h5>
                        <p class="staff-profile-branch"><?php echo esc_html( $location ); ?></p>
                    <?php endif; ?>

                    <?php if ( $has_address ) : ?>
                        <h5 class="staff-profile-header-address"><?php echo esc_html__( 'Address', 'cimne' ); ?></h5>
                        <p class="staff-profile-address">
                            <?php if ( '' !== $address_line ) : ?>
                                <?php echo esc_html( $address_line ); ?>
                            <?php endif; ?>
                            <?php if ( '' !== $address_line && '' !== $address_locality ) : ?>
                                <br>
                            <?php endif; ?>
                            <?php if ( '' !== $address_locality ) : ?>
                                <?php echo esc_html( $address_locality ); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="staff-profile-content">
            <?php if ( '' !== $department ) : ?>
                <div class="row cimne-directory-profile-row cimne-directory-profile-row--department">
                    <div class="column-left">
                        <h5><?php echo esc_html__( 'Group/Department', 'cimne' ); ?></h5>
                    </div>
                    <div class="column-right">
                        <p><?php echo esc_html( $department ); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $position_items ) ) : ?>
                <div class="row cimne-directory-profile-row cimne-directory-profile-row--position">
                    <div class="column-left">
                        <h5><?php echo esc_html__( 'Position in CIMNE', 'cimne' ); ?></h5>
                    </div>
                    <div class="column-right">
                        <p class="cimne-directory-profile-positions">
                            <?php foreach ( $position_items as $index => $position_item ) : ?>
                                <?php
                                if ( ! is_array( $position_item ) ) {
                                    continue;
                                }

                                $political_role = isset( $position_item['political_role'] )
                                    ? trim( (string) $position_item['political_role'] )
                                    : '';
                                $role_description = isset( $position_item['role_description'] )
                                    ? trim( (string) $position_item['role_description'] )
                                    : '';

                                if ( '' === $political_role && '' === $role_description ) {
                                    continue;
                                }
                                ?>
                                <?php if ( $index > 0 ) : ?><br><?php endif; ?>
                                <span class="cimne-directory-profile-position">
                                    <?php if ( '' !== $political_role ) : ?>
                                        <strong class="cimne-directory-profile-political-role"><?php echo esc_html( $political_role ); ?></strong><?php if ( '' !== $role_description ) : ?>, <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ( '' !== $role_description ) : ?><?php echo esc_html( $role_description ); ?><?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $scopus_id && '' !== $scopus_url ) : ?>
                <div class="row cimne-directory-profile-row cimne-directory-profile-row--scopus">
                    <div class="column-left"><h5><?php echo esc_html__( 'Scopus ID', 'cimne' ); ?></h5></div>
                    <div class="column-right">
                        <p><a href="<?php echo esc_url( $scopus_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $scopus_id ); ?></a></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $orcid && '' !== $orcid_url ) : ?>
                <div class="row cimne-directory-profile-row cimne-directory-profile-row--orcid">
                    <div class="column-left"><h5><?php echo esc_html__( 'ORCID', 'cimne' ); ?></h5></div>
                    <div class="column-right">
                        <p><a href="<?php echo esc_url( $orcid_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $orcid ); ?></a></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $researcher_id && '' !== $researcher_url ) : ?>
                <div class="row cimne-directory-profile-row cimne-directory-profile-row--researcher-id">
                    <div class="column-left"><h5><?php echo esc_html__( 'Researcher ID', 'cimne' ); ?></h5></div>
                    <div class="column-right">
                        <p><a href="<?php echo esc_url( $researcher_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $researcher_id ); ?></a></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $google_scholar && '' !== $google_url ) : ?>
                <div class="row cimne-directory-profile-row cimne-directory-profile-row--google-scholar">
                    <div class="column-left"><h5><?php echo esc_html__( 'Google Scholar', 'cimne' ); ?></h5></div>
                    <div class="column-right">
                        <p><a href="<?php echo esc_url( $google_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $google_scholar ); ?></a></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $cv ) : ?>
                <div class="row cimne-directory-profile-row cimne-directory-profile-row--cv">
                    <div class="column-left"><h5><?php echo esc_html__( 'CV', 'cimne' ); ?></h5></div>
                    <div class="column-right cimne-directory-profile-cv">
                        <?php echo wp_kses_post( $cv ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
