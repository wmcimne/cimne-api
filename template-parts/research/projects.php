<?php
/**
 * Main interactive Projects shell shared by:
 * - Cluster / Research Group pages.
 * - The global Projects page.
 *
 * Ongoing / Finished controls are rendered by Divi outside this template.
 * This template only owns the dynamic projects content area.
 *
 * Expected $args:
 * - project_context: research|global
 * - research_unit_code
 * - projects_page_url
 * - ongoing_html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$project_context = isset( $args['project_context'] ) && 'global' === $args['project_context'] ? 'global' : 'research';
$unit_code       = isset( $args['research_unit_code'] ) ? (string) $args['research_unit_code'] : '';
$ongoing           = isset( $args['ongoing_html'] ) ? (string) $args['ongoing_html'] : '';
$projects_page_url = isset( $args['projects_page_url'] ) ? (string) $args['projects_page_url'] : '';
$list_url          = rest_url( 'cimne/v1/research-projects/list' );
$detail_url      = rest_url( 'cimne/v1/research-projects/detail' );
?>
<div
    class="cimne-research-projects"
    data-cimne-projects
    data-cimne-research-projects
    data-project-context="<?php echo esc_attr( $project_context ); ?>"
    data-unit-code="<?php echo esc_attr( $unit_code ); ?>"
    data-projects-page-url="<?php echo esc_url( $projects_page_url ); ?>"
    data-list-url="<?php echo esc_url( $list_url ); ?>"
    data-detail-url="<?php echo esc_url( $detail_url ); ?>"
>
    <div class="cimne-research-projects__main">
        <div class="cimne-research-projects__notice" aria-live="polite" data-project-notice></div>
        <div class="cimne-research-projects__content" data-project-content>
            <?php echo $ongoing; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by trusted theme template. ?>
        </div>
    </div>
</div>
