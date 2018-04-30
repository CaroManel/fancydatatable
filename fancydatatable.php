<?php
/**
 * Plugin Name:     Fancy DataTables
 * Plugin URI:      https://github.com/TheCliWoman/fancydatatable
 * Description:     Shows posts as datatables.
 * Author:          Caro Manel
 * Author URI:      https://github.com/TheCliWoman/fancydatatable
 * Text Domain:     fancydatatable
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         fancydatatable
 */

// Samll image to use in the thumbnail column.
add_image_size( 'fancydatatable-thumbnail', 50, 50 );

// Datatabe style
function fancydatatable_assets() {
	wp_enqueue_script( 'jquery' );

	wp_register_style( 'fancydatatable_css', 'https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css' );
	wp_enqueue_style( 'fancydatatable_css' );


	wp_register_script( 'fancydatatable_js', 'https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js', array( 'jquery' ), '1.0', false );
	wp_enqueue_script( 'fancydatatable_js' );
}
add_action( 'wp_enqueue_scripts', 'fancydatatable_assets' );

// Custom style.
function fancydatatable_styles() {
	?>
	<style>
		td.details-control {
			background: url( '/wp-content/plugins/fancydatatable/arrow_right.png' ) no-repeat top center;
			cursor: pointer;
			padding: 30px 20px 20px !important;
		}
		tr.shown td.details-control {
			background: url( '/wp-content/plugins/fancydatatable/arrow_down.png' ) no-repeat top center;
			padding: 30px 20px 20px !important;
		}
		#fancydatatable ul {
			list-style-type: none;
			margin: 0;
			padding: 0;
		}
		#fancydatatable ul li {
			display:inline;
		}

		tbody tr:nth-child(2n+1) {
    background-color: #fff;
}

#fancydatatable a {
	color: #fe8a02;
}

#fancydatatable a:hover {
	color: #666;
	font-weight: 600;
}

	</style>
	<?php
}
add_action( 'wp_head', 'fancydatatable_styles', 100 );

// Datatab;e js to build the table.
function fancydatatable_js() {
	?>
	<script type = "text/javascript" >

		jQuery( document ).ready( function () {
			// Init dataTable
			var table = jQuery( '#fancydatatable' ).DataTable( {
				"columnDefs": [ {
"targets": 0,
"orderable": false
} ],
"order": [[ 2, "desc" ]]
			} );

			// Add event listener for opening and closing details
			jQuery( '#fancydatatable tbody' ).on( 'click', 'td.details-control', function () {
				var tr = jQuery( this ).closest( 'tr' );
				var row = table.row( tr );
				var keyme;

				if ( row.child.isShown() ) {
					row.child.hide();
					tr.removeClass( 'shown' );
				} else {

					keyme = tr.attr( 'data-id' );

					row.child( function() {
						jQuery.ajax({
							async: false,
							cache: false,
							timeout: 30000,
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							data: {
								'action': 'fancydatatable_ajax_request',
								'id': keyme
							},
							success: function  (data) {
								keyme = data;
								return keyme;

							},
							error: function (errorThrown) {
								console.log(errorThrown);
							}
						});
						return keyme;

					}).show();

					tr.addClass( 'shown' );
				}
			} ); //click
		} );

	</script>
	<?php
}
add_action( 'wp_footer', 'fancydatatable_js', 100 );

function fancydatatable_build( $atts ) {
	extract(
		shortcode_atts(
			array(
				'category_name' => 'all',
			), $atts
		)
	);

	$allow_html = array(
		'ul'     => array(
			'li' => array(),
		),
		'a'      => array(
			'href'  => array(),
			'title' => array(),
		),
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
	);

	wp_reset_query();

	ob_start();

	$args = array(
		'posts_per_page' => -1, // all post with this category
	);

	$category_name = ( 'all' == $category_name ) ? -1 : $category_name;

	array_push( $args, $category_name );


	$query = new WP_Query( $args );

	if ( $query->have_posts() ) :
		echo '<table id="fancydatatable" class="table table-striped table-bordered dataTable" style="width:100%">';
		echo '<thead>';
		echo '<tr>';
			echo '<th></th>';
			echo '<th>Image</th>';
			echo '<th>Title</th>';
			echo '<th>Description</th>';
			echo '<th>Category</th>';
			echo '<th>Tags</th>';
			echo '</tr>';
		echo '</thead>';
		while ( $query->have_posts() ) :
			$query->the_post();

			$thumbnail = get_the_post_thumbnail( get_the_ID(), 'fancydatatable-thumbnail' );
			$title     = apply_filters( 'fancydatatable_the_title', get_the_title() );
			$excerpt   = apply_filters( 'fancydatatable_the_excerpt', get_the_excerpt() );
			$cat       = apply_filters( 'fancydatatable_the_categories', get_the_category_list(', ') );
			$tags      = apply_filters( 'fancydatatable_the_tags', get_the_tag_list('',', ') );
			?>
			<tr data-id="<?php echo esc_attr( get_the_ID() ); ?>">
				<td class="details-control"></td>
				<td>
					<?php
					if ( $thumbnail ) {
						echo $thumbnail;
					} else {
						echo 'No Image';
					}
					?>
				</td>
				<td><?php echo esc_attr( $title ); ?></td>
				<td><?php echo wp_kses( $excerpt, $allow_html ); ?></td>
				<td><?php echo wp_kses( $cat, $allow_html ); ?></td>
				<td><?php echo wp_kses( $tags, $allow_html ); ?>
					<?php
					if ( current_user_can( 'edit_post', get_the_ID() ) ) {
						// Edit button if current user can edit
						echo '<span class="edit-link"><a class="post-edit-link" href="/wp-admin/post.php?post=' . get_the_ID() . '&action=edit">EDIT</a></span>';
					}
					?>
				</td>
			</tr>
		<?php
		endwhile;
		echo '</table>';
	endif;

	$output_string = ob_get_contents();
	ob_end_clean();
	return $output_string;
	wp_reset_postdata();
}
add_shortcode( 'fancydatatable', 'fancydatatable_build' );

function fancydatatable_ajax_request() {
	$return = '';

	if ( isset( $_REQUEST ) ) {
		$content_post = get_post( $_REQUEST['id'] );
		$content      = $content_post->post_content;
		$content      = apply_filters( 'the_content', $content );
		$content      = str_replace( ']]>', ']]&gt;', $content );
		$return       = '<table cellpadding="6" cellspacing="0" border="0" style="padding-left:50px;">
		<tr>
		<td>' . $content . '</td>
		</tr>
		</table>';

		echo $return;
	}
	wp_die();
}

add_action( 'wp_ajax_fancydatatable_ajax_request', 'fancydatatable_ajax_request' );
add_action( 'wp_ajax_nopriv_fancydatatable_ajax_request', 'fancydatatablez_ajax_request' );

