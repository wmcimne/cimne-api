<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;



/*================================================
#ENQUEUE PARENT ACTION
================================================*/
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
// END ENQUEUE PARENT ACTION




/*====================================================
# Imagen de cabecera aleatoria en la página de inicio
=======================================================*/
function cimne_add_random_home_header_style() {
    // Solo se ejecuta si es la página de inicio configurada
    if ( is_front_page() ) {

        $uploads = wp_get_upload_dir();
		      
        $imagenes = array(
            $uploads['baseurl'] . '/2025/05/header3-1.jpg',
            $uploads['baseurl'] . '/2025/05/header2dark-1.jpg',
            $uploads['baseurl'] . '/2025/05/header1-1.jpg'
        );

        $imagen_aleatoria = $imagenes[array_rand($imagenes)];
		

        echo '<style type="text/css">
            .home-header-image {
                background-image: url("' . esc_url($imagen_aleatoria) . '") !important;
            }
        </style>';
    }
}
add_action('wp_head', 'cimne_add_random_home_header_style' );



/*================================================
#remove WP Version Info
================================================*/
remove_action('wp_head', 'wp_generator');


    
/*================================================
# Módulo blog Divi sin entradas multidioma
================================================*/

function cimne_translate_divi_no_results($translated, $text, $domain) {
    // Verifica si el texto pertenece a Divi y es el mensaje de "no hay resultados"
    if ($domain === 'Divi' && $text === 'No Results Found') {

        $current_language = apply_filters( 'wpml_current_language', null );
        
        // Detecta el idioma activo de WPML
        switch ($current_language) {
            case 'en':
                $translated = 'Sorry, no posts were found matching your criteria.';
                break;
            case 'ca':
                $translated = 'Ho sentim, no s\'han trobat entrades en aquesta categoria.';
                break;
            case 'es':
            default:
                $translated = 'Lo sentimos, no se encontraron entradas en esta categoría.';
                break;
        }
    }
    return $translated;
}
add_filter('gettext_Divi', 'cimne_translate_divi_no_results', 10, 3);



/*================================================
# Carga organizada y condicional de JavaScript
================================================*/

/**
 * Obtiene una versión automática basada en la fecha de modificación.
 *
 * Cuando se modifica un archivo, cambia su URL y se evita que el navegador
 * o WP Super Cache mantengan una versión anterior.
 *
 * @param string $relative_path Ruta relativa desde el tema hijo.
 *
 * @return string
 */
function cimne_asset_version( string $relative_path ): string {
	$absolute_path = get_stylesheet_directory() . $relative_path;

	if ( file_exists( $absolute_path ) ) {
		return (string) filemtime( $absolute_path );
	}

	return (string) wp_get_theme()->get( 'Version' );
}

/**
 * Obtiene el contenido de la entrada o página actual.
 *
 * Utiliza una variable estática para consultar el objeto únicamente una vez
 * durante cada petición.
 *
 * @return string
 */
function cimne_get_current_post_content(): string {
	static $content = null;

	if ( null !== $content ) {
		return $content;
	}

	$content = '';

	if ( ! is_singular() ) {
		return $content;
	}

	$post = get_queried_object();

	if ( $post instanceof WP_Post ) {
		$content = (string) $post->post_content;
	}

	return $content;
}

/**
 * Comprueba si el contenido actual contiene una cadena determinada.
 *
 * @param string $search Cadena que se quiere localizar.
 *
 * @return bool
 */
function cimne_current_content_contains( string $search ): bool {
	if ( '' === $search ) {
		return false;
	}

	return false !== strpos( cimne_get_current_post_content(), $search );
}

/**
 * Indica si la página utiliza la API de métricas de CIMNE.
 *
 * @return bool
 */
function cimne_page_uses_metrics_api(): bool {
	return cimne_current_content_contains( 'id="api-call"' ) || cimne_current_content_contains( "id='api-call'" );
}

/**
 * Indica si la página utiliza datos procedentes de Google Sheets.
 *
 * @return bool
 */
function cimne_page_uses_google_sheets(): bool {
	return cimne_current_content_contains( 'id="gsheets-call"' ) || cimne_current_content_contains( "id='gsheets-call'" );
}

/**
 * Indica si la página contiene una inicialización de Slick Carousel.
 *
 * Detecta código insertado en el contenido de Divi, normalmente dentro
 * de un módulo Código.
 *
 * @return bool
 */
function cimne_page_uses_slick(): bool {
	$content = cimne_get_current_post_content();

	if ( '' === $content ) {
		return false;
	}

	return false !== strpos( $content, '.slick(' )
		|| false !== strpos( $content, '.slick (' )
		|| false !== strpos( $content, 'slick({' );
}

/**
 * Registra y carga los scripts del tema hijo.
 *
 * Los scripts comunes de la API de métricas y Google Sheets se registran
 * una sola vez. WordPress carga automáticamente sus dependencias cuando
 * se encola el script final correspondiente.
 *
 * @return void
 */
function cimne_enqueue_theme_scripts(): void {
	$theme_uri = get_stylesheet_directory_uri();
	$uses_slick = cimne_page_uses_slick();
	$uses_metrics_api = cimne_page_uses_metrics_api();
	$uses_google_sheets = cimne_page_uses_google_sheets();
	$defer_args = array(
		'strategy' => 'defer',
		'in_footer' => true,
	);

	/*
	 * Slick Carousel.
	 *
	 * Solo se registra cuando el contenido actual contiene una
	 * inicialización de Slick.
	 */
	if ( $uses_slick ) {
		wp_register_script(
			'cimne-slick',
			$theme_uri . '/js/slick.min.js',
			array( 'jquery' ),
			cimne_asset_version( '/js/slick.min.js' ),
			$defer_args
		);
	}

	/*
	 * Script global del tema.
	 *
	 * Cuando la página utiliza Slick, se añade como dependencia para
	 * garantizar que slick.min.js se ejecute antes que functions.js.
	 */
	$global_dependencies = array();

	if ( $uses_slick ) {
		$global_dependencies[] = 'cimne-slick';
	}

	wp_enqueue_script(
		'cimne-global',
		$theme_uri . '/js/functions.js',
		$global_dependencies,
		cimne_asset_version( '/js/functions.js' ),
		$defer_args
	);

	/*
	 * Scripts comunes para la API de métricas y Google Sheets.
	 *
	 * Solo se registran si la página utiliza al menos una de estas
	 * funcionalidades.
	 */
	if ( $uses_metrics_api || $uses_google_sheets ) {
		wp_register_script(
			'cimne-locale',
			$theme_uri . '/js/locale.js',
			array(),
			cimne_asset_version( '/js/locale.js' ),
			$defer_args
		);
		wp_register_script(
			'cimne-crypt',
			$theme_uri . '/js/crypt.js',
			array(),
			cimne_asset_version( '/js/crypt.js' ),
			$defer_args
		);
		wp_register_script(
			'cimne-views',
			$theme_uri . '/js/views.js',
			array( 'cimne-locale', 'cimne-crypt' ),
			cimne_asset_version( '/js/views.js' ),
			$defer_args
		);
	}

	/*
	 * API de métricas.
	 *
	 * Orden resultante:
	 *
	 * locale.js
	 * crypt.js
	 * views.js
	 * services.js
	 */
	if ( $uses_metrics_api ) {
		wp_enqueue_script(
			'cimne-services',
			$theme_uri . '/js/services.js',
			array( 'cimne-locale', 'cimne-crypt', 'cimne-views' ),
			cimne_asset_version( '/js/services.js' ),
			$defer_args
		);
	}

	/*
	 * Integración con Google Sheets.
	 *
	 * Orden resultante:
	 *
	 * locale.js
	 * crypt.js
	 * views.js
	 * gsheets_data.js
	 */
	if ( $uses_google_sheets ) {
		wp_enqueue_script(
			'cimne-google-sheets',
			$theme_uri . '/js/gsheets_data.js',
			array( 'cimne-locale', 'cimne-crypt', 'cimne-views' ),
			cimne_asset_version( '/js/gsheets_data.js' ),
			$defer_args
		);
	}
}

add_action( 'wp_enqueue_scripts', 'cimne_enqueue_theme_scripts', 20 );

/*================================================
# CIMNE API - New web services integration
================================================*/

require_once get_stylesheet_directory() . '/includes/api/cache.php';
require_once get_stylesheet_directory() . '/includes/api/client.php';
require_once get_stylesheet_directory() . '/includes/api/normalizers.php';

/*================================================
# Research pages - Dynamic API components
================================================*/

require_once get_stylesheet_directory() . '/includes/research/bootstrap.php';
require_once get_stylesheet_directory() . '/includes/awards/bootstrap.php';

/*================================================
# Directory pages - Dynamic API components
================================================*/

require_once get_stylesheet_directory() . '/includes/directory/bootstrap.php';

/*================================================
# Includes del tema
================================================*/
require_once get_stylesheet_directory() . '/includes/navigation/navigation.php';

require_once get_stylesheet_directory() . '/includes/theme-setup.php';
require_once get_stylesheet_directory() . '/includes/login.php';
require_once get_stylesheet_directory() . '/includes/shortcodes.php';

/*================================================
# Includes para Ajax Listings
================================================*/

require_once get_stylesheet_directory() . '/includes/ajax-listings/helpers.php';
require_once get_stylesheet_directory() . '/includes/ajax-listings/pagination.php';
require_once get_stylesheet_directory() . '/includes/ajax-listings/assets.php';
require_once get_stylesheet_directory() . '/includes/ajax-listings/events.php';
require_once get_stylesheet_directory() . '/includes/ajax-listings/news.php';
require_once get_stylesheet_directory() . '/includes/ajax-listings/workshops.php';
