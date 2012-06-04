<?php

class Authority_CSV_Parser implements Iterator
{
	protected $headers;

	protected $rows;

	protected $csv;

	protected $key;
	protected $current;

	public function parse( $file )
	{
		$this->csv = fopen( $file, 'r' );
		$this->headers = fgetcsv( $this->csv );
		next( $this );
	}

	public function rewind()
	{
		// this will get immediately bumped in $this->next()
		$this->key = -1;

		fseek( $this->csv, 0 );
		fgetcsv( $this->csv );

		$this->next();
	}

	public function current()
	{
		return $this->current;
	}

	public function key()
	{
		return key( $this->rows );
	}

	public function next()
	{
		$this->key += 1;
		$next = fgetcsv( $this->csv );

		if( $next === false )
		{
			$this->key = false;
			return false;
		}

		$this->current = array_combine( $this->headers, $next );
		return $this->current;
	}

	public function valid()
	{
		return $this->key !== false;
	}

	/**
	 * Return position in CSV.
	 */
	public function tell()
	{
		return ftell( $this->csv );
	}

	/**
	 * Seek to a position in the CSV.
	 * @see fseek
	 */
	public function seek( $offset, $whence = SEEK_SET )
	{
		$pos = fseek( $this->csv, $offset, $whence );
	}
}
