<?php

/**
 * Scriblio Authority Importer
 *
 * Modeled after wordpress-importer.
 *
 * @see http://plugins.svn.wordpress.org/wordpress-importer/trunk/wordpress-importer.php
 */


if ( ! defined( 'WP_LOAD_IMPORTERS' ) )
	return;

require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) )
{
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require $class_wp_importer;
}

class Authority_Importer
{
	protected $parser;

	public function __construct()
	{
	}

	public function dispatch()
	{
		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
		switch ( $step ) {
			case 0:
				$this->greet();
				break;
			case 1:
				check_admin_referer( 'import-upload' );
				if( $this->handle_upload() )
				{
					set_time_limit(0);
					$file = get_attached_file( $this->id );
					$this->parse( $file );
					$this->next();
				}
				break;
			case 2:
				check_admin_referer( 'import-scriblio-csv' );
				$this->id = (int) $_POST['import_id'];
				$file = get_attached_file( $this->id );
				$this->parse( $file );
				$position = isset( $_POST['position'] ) ? (int) $_POST['position'] : 0;
				$new_position = $this->import( compact( 'position' ) );

				if( $new_position != false )
				{
					$this->next( $new_position );
				}

				break;
		}

		$this->footer();
	}

	public function handle_upload()
	{
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) )
		{
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'scribauth-importer' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		}
		elseif ( ! file_exists( $file['file'] ) )
		{
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'scribauth-importer' ) . '</strong><br />';
			printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'wordpress-importer' ), esc_html( $file['file'] ) );
			echo '</p>';
			return false;
		}

		$this->id = (int) $file['id'];

		return true;
	}

	public static function init() {
		register_importer( 'scriblio_authority', 'Scriblio Authority CSV', 'Import Scriblio Authority records from CSV.', array( Authority::importer(), 'dispatch' ) );
	}//end init

	public function next( $position = 0 )
	{
		?>
		<form class="scrib-auth-importer" action="<?php echo admin_url( 'admin.php?import=scriblio_authority&amp;step=2' ); ?>" method="post">
			<?php wp_nonce_field( 'import-scriblio-csv' ); ?>
			<input type="hidden" name="import_id" value="<?php echo $this->id; ?>" />
			<input type="hidden" name="position" value="<?php echo $position; ?>" />
			<input type="submit" value="Load More Records">
		</form>
		<script type="text/javascript">
		(function($){
			$(function(){
				function doSubmit() {
					$('.scrib-auth-importer').submit();
				}
				setTimeout( doSubmit, 2000 );
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Run the import process
	 */
	public function import( $args = '' )
	{
		$defaults = array(
			'position' => 0,
			'limit' => 20,
		);
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );

		if ( $position )
		{
			$this->parser->seek( $position );
		}

		$count = 0;
		while ( false !== ( $line = $this->parser->next() ) )
		{
			if ( is_wp_error( $line ) )
			{
				$this->import_error( $line );
				continue;
			}

			$result = $this->import_one( $line );

			if ( is_wp_error( $result ) )
			{
				$this->import_error( $result );
			}
			else
			{
				printf( '<li>Added "%s" to "%s" in %s.</li>', $line['name'],
					$line['Corrected Name'], $line['taxonomy'] );
			}

			if ( ++$count > $limit )
			{
				return $this->parser->tell();
			}
		}

		return false;
	}

	function import_error( $error )
	{
		printf( '<li>%s (tax:%s, name:%s, corrected name: %s)</li>', $error->get_error_message(),
			$line['taxonomy'], $line['name'], $line['Corrected Name'] );
	}

	/**
	 * Import a single record from the CSV as an authority record.
	 */
	function import_one( $record )
	{
		if ( empty($record['taxonomy']) ||
			empty($record['Corrected Name']) ||
			empty($record['name'])
		) {
			return new WP_Error( 'missing-field', 'One or more fields were missing from the record' );
		}

		$taxonomy = $record['taxonomy'];

		$primary_term_name = $record['Corrected Name'];
		$alias_term_name = $record['name'];

		$primary_term = $this->get_or_insert_term( $primary_term_name, $taxonomy );
		$alias_term = $this->get_or_insert_term( $alias_term_name, $taxonomy );

		if ( false === ( $term_authority = Authority::post_type()->get_term_authority( $primary_term ) ) )
		{
			// Creating a new authority record
			$post_id = Authority::post_type()->create_authority_record( $primary_term, array( $alias_term ) );

			if( FALSE === $post_id )
			{
				return new WP_Error( 'create-failed', 'Could not create authority record' );
			}
		}
		else
		{
			// Adding an alias to an existing record
			if( ! Authority::post_type()->authority_has_alias( $term_authority, $alias_term ) )
			{
				$term_authority->alias_terms[] = $alias_term;
				Authority::post_type()->update_post_meta( $term_authority->post_id, $term_authority );
			}
		}
	}

	function get_or_insert_term( $term_name, $taxonomy )
	{
		if ( $term_id = get_term_by( 'name', $term_name, $taxonomy ) )
		{
			$term = get_term( $term_id, $taxonomy );
		}
		else
		{
			$term = wp_insert_term( $term_name, $taxonomy );

			if ( is_wp_error( $term ) )
				return $term;

			$term = get_term( $term['term_id'], $taxonomy );
		}

		return $term;
	}

	function import_options()
	{
		$j = 0;
?>
<form action="<?php echo admin_url( 'admin.php?import=scriblio_authority&amp;step=2' ); ?>" method="post">
	<?php wp_nonce_field( 'import-scriblio-csv' ); ?>
	<input type="hidden" name="import_id" value="<?php echo $this->id; ?>" />
	<p class="submit"><input type="submit" class="button" value="<?php esc_attr_e( 'Submit', 'wordpress-importer' ); ?>" /></p>
</form>
<?php
	}

	public function parse( $file )
	{
		$this->parser = new Authority_CSV_Parser;
		$this->parser->parse( $file );
	}

	public function header()
	{
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __( 'Import Scriblio Authority CSV', 'scribauth-importer' ) . '</h2>';
	}

	public function footer()
	{
		echo '</div>';
	}

	public function hooks()
	{
	}

	public function greet()
	{
		wp_import_upload_form( 'admin.php?import=scriblio_authority&amp;step=1' );
	}
}
