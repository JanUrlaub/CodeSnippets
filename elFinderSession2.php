<?php

/**
 * elFinder - file manager for web.
 * Session Wrapper Class.
 *
 * @package elfinder
 * @author Naoki Sawada
 **/

class elFinderSession2 implements elFinderSessionInterface
{
	protected $content = array();
	
    /**
     * {@inheritdoc}
     */
	public function start()
	{
		return $this;
	}
	
    /**
     * {@inheritdoc}
     */
	public function close()
	{
		return $this;
	}
	
    /**
     * {@inheritdoc}
     */
	public function get($key, $empty = null)
	{
		if(!isset($this->content[$key])) return;
		$data = $this->content[$key];
		
		$checkFn = null;
		if (! is_null($empty)) {
			if (is_string($empty)) {
				$checkFn = 'is_string';
			} elseif (is_array($empty)) {
				$checkFn = 'is_array';
			} elseif (is_object($empty)) {
				$checkFn = 'is_object';
			} elseif (is_float($empty)) {
				$checkFn = 'is_float';
			} elseif (is_int($empty)) {
				$checkFn = 'is_int';
			}
		}
		
		if (is_null($data) || ($checkFn && ! $checkFn($data))) {
			$data = $empty;
		}
		
		return $data;
	}
	
    /**
     * {@inheritdoc}
     */
	public function set($key, $data)
	{
		$this->content[$key] = $data;
		
		return $this;
	}
	
    /**
     * {@inheritdoc}
     */
	public function remove($key)
	{
		unset($this->content[$key]);
		
		return $this;
	}
	
	protected function encodeData($data)
	{
		$data = base64_encode(serialize($data));
		return $data;
	}
	
	protected function decodeData($data)
	{
		
		if (is_string($data)) {
			if (($data = base64_decode($data)) !== false) {
				$data = unserialize($data);
			} else {
				$data = null;
			}
		} else {
			$data = null;
		}
		
		return $data;
	}

	protected function session_start_error($errno , $errstr) {}
}
