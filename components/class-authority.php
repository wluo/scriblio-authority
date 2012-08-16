<?php

class Authority
{
	public static $plugin_dir = 'scriblio-authority';
	private static $id_base = 'scrib-authority';

	public static function easy_terms()
	{
		return static::singleton( 'Authority_EasyTerms' );
	}//end easy_terms

	public static function importer()
	{
		return static::singleton( 'Authority_Importer' );
	}//end importer

	public static function init()
	{
		static::post_type();
		static::easy_terms();
	}//end init

	public static function post_type() 
	{
		return static::singleton( 'Authority_PostType' );
	}//end post_type

	public static function singleton( $class )
	{
		static $singletons = array();

		if( ! isset( $singletons[ $class ] ) ) {
			$singletons[ $class ] = new $class;
		}//end if

		return $singletons[ $class ];
	}//end singleton

	public static function supported_taxonomies( $support = null ) 
	{
		static $taxonomies = array();

		if ( $support ) 
		{
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

			$purge = array_diff( array_keys( $taxonomies ), $support );

			foreach( $purge as $remove ) 
			{
				unset( $taxonomies[ $remove ] );
			}//end foreach

			// sort taxonomies by the singular name
			uasort( $taxonomies, function( $a, $b ) {
				if ( $a->labels->singular_name == $b->labels->singular_name )
				{
					return 0;
				}//end if

				if ( 'post_tag' == $b->name  )
				{
					return -1;
				}//end if

				return $a->labels->singular_name < $b->labels->singular_name ? -1 : 1;
			});
		}//end if

		return $taxonomies;
	}//end supported_taxonomies
}//end class
