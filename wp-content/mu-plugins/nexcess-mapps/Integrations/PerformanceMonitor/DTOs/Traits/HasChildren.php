<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Traits;

/**
 * Allows DTOs to have children.
 */
trait HasChildren {

	/** @var array */
	protected $children = [];

	/**
	 * Returns the children of this DTO, optionally filtered by class name.
	 *
	 * @template T
	 *
	 * @param class-string<T>|null $class_name
	 *
	 * @return array<T>
	 */
	public function getChildren( $class_name = null ) {
		if ( $class_name ) {
			$children = [];
			foreach ( $this->children as $child ) {
				if ( $child instanceof $class_name ) {
					$children[] = $child;
				}
			}
			return $children;
		}
		return $this->children;
	}

	/**
	 * Returns the first child of this DTO, optionally filtered by class name.
	 *
	 * @template T
	 *
	 * @param class-string<T>|null $class_name
	 *
	 * @return T|null
	 */
	public function getFirstChild( $class_name = null ) {
		$children = $this->getChildren( $class_name );
		return isset( $children[0] ) ? $children[0] : null;
	}

	/**
	 * Adds a child to this DTO.
	 *
	 * @param mixed $child
	 */
	public function addChild( $child ) {
		$this->children[] = $child;
	}
}
