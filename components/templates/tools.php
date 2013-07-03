<div class="wrap go-featured-admin">
	<?php screen_icon( 'tools' ); ?>
	<h2>Authority Record Tools</h2>

	<div class="tool-box">
		<h3 class="title">Report on term usage and authority by taxonomy</h3>
		<ul>
			<?php
			foreach( authority_record()->supported_taxonomies() as $tax_obj )
			{
			?>
				<li><a href="<?php echo admin_url( 'admin-ajax.php?action=authority_term_report&taxonomy=' . $tax_obj->name ); ?>">Download <?php echo strtolower( $tax_obj->label ); ?> CSV</a></li>
			<?php
			}
			?>
		</ul>
	</div>

	<div class="tool-box">
		<h3 class="title">Term analysis</h3>
		<ul>
			<li><a href="<?php echo admin_url( 'admin-ajax.php?action=authority_stem_report' ); ?>">Download term stem report CSV</a></li>
			<li><a href="<?php echo admin_url( 'admin-ajax.php?action=authority_spell_report&key=PASTE_YOUR_AZURE_DATAMARKET_KEY_HERE' ); ?>">Download term spell check report CSV</a></li>
		</ul>
	</div>

	<?php

	if( current_user_can( 'manage_options' ))
	{
	?>
		<div class="tool-box">
			<h3 class="title">Enforce term authority</h3>
			<p>This takes a while, but it loops over all authority records and enforces term authority on all posts.</p>

			<p><a href="<?php echo admin_url( 'admin-ajax.php?action=authority_enforce_all_authority' ); ?>" target="_blank">Enforce authority on all authority records</a>
		</div>

		<div class="tool-box">
			<h3 class="title">Advanced: clean numeric term slug suffixes</h3>
			<p>Warning: don't attempt this unless you've read the code and know exactly what it does.</p>

			<p><a href="<?php echo admin_url( 'admin-ajax.php?action=authority_term_suffix_cleaner' ); ?>" target="_blank">Clean numeric term slug suffixes</a>, use same term_id for same term_name in different taxonomies</p>
		</div>

		<div class="tool-box">
			<h3 class="title">Advanced: update term counts</h3>
			<p>Warning: don't attempt this unless you've read the code and know exactly what it does.</p>

			<p><a href="<?php echo admin_url( 'admin-ajax.php?action=authority_update_term_counts' ); ?>" target="_blank">Update term counts</a></p>
		</div>

		<div class="tool-box">
			<h3 class="title">Advanced: create authority records</h3>
			<p>Warning: don't attempt this unless you've read the code and know exactly what it does.</p>
			<p>Create authority records for terms shared in multiple taxonomies</p>
			<ul>
				<?php
				$taxs = authority_record()->supported_taxonomies();

				foreach( $taxs as $tax_obj_a )
				{
					foreach( $taxs as $tax_obj_b )
					{
						if( $tax_obj_a->name === $tax_obj_b->name )
							continue;
						?>
						<li><a href="<?php echo admin_url( 'admin-ajax.php?action=authority_create_authority_records&old_tax='. $tax_obj_b->name .'&new_tax='. $tax_obj_a->name .'&paged=0&posts_per_page=3' ); ?>">Make <?php echo strtolower( $tax_obj_b->label ); ?> authoritative over <?php echo strtolower( $tax_obj_a->label ); ?></a> where their terms intersect</li>
						<?php
					}
				}
				?>
			</ul>
	<?php
	}
	?>

</div>
