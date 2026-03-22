<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

use JsonSerializable;

/**
 * Makes DTOs serializable to JSON.
 */
abstract class JsonSerializableDTO implements JsonSerializable {

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		$data = [];

		foreach ( get_object_vars( $this ) as $key => $value ) {
			if ( $value instanceof JsonSerializable ) {
				$data[ $key ] = $value->jsonSerialize();
			} else {
				$data[ $key ] = $value;
			}
		}
		return $data;
	}

	/**
	 * Returns a JSON-encoded string representation of the object.
	 */
	public function getJson() {
		return wp_json_encode( $this );
	}
}
