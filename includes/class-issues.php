<?php

class Issues {

	/**
	 * Zenhub token
	 * @var string
	 */
	private $token;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$config      = (object) json_decode( file_get_contents( WC_DECISION_MATRIC_PATH . '/config/config.json' ) );
		$this->token = $config->token;
	}

	/**
	 * Get issue data.
	 *
	 * @return array of objects
	 */
	public function get_data( $force_refresh = false ) {
		$data = $this->get_cache();

		if ( ! $data || $force_refresh ) {
			$issues = $this->get_data_from_api();
		} else {
			$issues = json_decode( $data );
		}

		return (array) $issues;
	}

	/**
	 * Get issues from the API.
	 */
	private function get_data_from_api() {
		$issues = array();
		$page   = 1;

		while ( 1 ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_URL, 'https://api.github.com/repos/woocommerce/woocommerce/issues?page=' . $page );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'User-Agent: WC Decision Matrix' ) );
			$response = curl_exec( $ch );
			curl_close( $ch );

			$response = json_decode( $response );

			if ( ! empty( $response ) && is_array( $response ) ) {
				$added = false;
				foreach ( $response as $issue ) {
					$issue        = (object) $issue;
					$issue_object = (object) array(
						'id'       => $issue->id,
						'number'   => $issue->number,
						'url'      => $issue->html_url,
						'title'    => $issue->title,
						'created'  => $issue->created_at,
						'comments' => $issue->comments,
						'impact'   => false,
					);
					if ( ! empty( $issue->labels ) ) {
						foreach ( $issue->labels as $label ) {
							$label = (object) $label;
							switch ( $label->name ) {
								case 'Impact: Very low' :
									$issue_object->impact = 'very-low';
									break;
								case 'Impact: Low' :
									$issue_object->impact = 'low';
									break;
								case 'Impact: Medium' :
									$issue_object->impact = 'medium';
									break;
								case 'Impact: High' :
									$issue_object->impact = 'high';
									break;
								case 'Impact: Mind blown' :
									$issue_object->impact = 'mind-blown';
									break;
							}
						}
					}
					if ( $issue_object->impact ) {
						$issues[ $issue->id ] = $issue_object;
					}
					$added = true;
				}
				if ( ! $added ) {
					break;
				}
			} else {
				break;
			}
			$page ++;
		}

		// Get zenhub data.
		foreach ( $issues as $id => $issue ) {
			$zenhub_data = $this->get_zenhub_data( $issue->number );

			if ( isset( $zenhub_data->estimate, $zenhub_data->estimate->value ) && $zenhub_data->estimate->value ) {
				$issues[ $id ]->estimate = $zenhub_data->estimate->value;
			} else {
				unset( $issues[ $id ] );
			}
		}

		$this->cache( json_encode( $issues ) );

		return $issues;
	}

	/**
	 * Get cached data.
	 *
	 * @return string
	 */
	private function get_cache() {
		if ( ! file_exists( WC_DECISION_MATRIC_PATH . '/cache/cache.json' ) ) {
			return '';
		}
		$data = file_get_contents(  WC_DECISION_MATRIC_PATH . '/cache/cache.json' );
		return $data;
	}

	/**
	 * Write to cache file.
	 */
	private function cache( $content ) {
		$file = fopen( WC_DECISION_MATRIC_PATH . '/cache/cache.json', 'w' );
		fwrite( $file, $content );
		fclose( $file );
	}

	/**
	 * Get ZenHub Issue data.
	 */
	private function get_zenhub_data( $issue ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'X-Authentication-Token: ' . $this->token ) );
		curl_setopt( $ch, CURLOPT_URL, 'https://api.zenhub.io/p1/repositories/2179920/issues/' . $issue );
		$response = curl_exec( $ch );
		curl_close( $ch );

		return (object) json_decode( $response );
 	}
}
