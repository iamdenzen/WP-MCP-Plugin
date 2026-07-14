<?php

namespace WP_MCP_Server\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooProductFormatter {

	public function summary( \WC_Product $product ): array {
		return [
			'id'                 => $product->get_id(),
			'type'               => $product->get_type(),
			'name'               => $product->get_name(),
			'slug'               => $product->get_slug(),
			'sku'                => $product->get_sku(),
			'url'                => get_permalink( $product->get_id() ),
			'price'              => $product->get_price(),
			'regular_price'      => $product->get_regular_price(),
			'sale_price'         => $product->get_sale_price(),
			'stock_status'       => $product->get_stock_status(),
			'catalog_visibility' => $product->get_catalog_visibility(),
			'image'              => $this->image( $product->get_image_id(), true ),
		];
	}

	public function full( \WC_Product $product ): array {
		$data = [
			'id'                 => $product->get_id(),
			'type'               => $product->get_type(),
			'name'               => $product->get_name(),
			'slug'               => $product->get_slug(),
			'sku'                => $product->get_sku(),
			'url'                => get_permalink( $product->get_id() ),
			'description'        => wp_strip_all_tags( $product->get_description() ),
			'short_description'  => wp_strip_all_tags( $product->get_short_description() ),
			'status'             => $product->get_status(),
			'featured'           => $product->get_featured(),
			'catalog_visibility' => $product->get_catalog_visibility(),
			'date_created'       => $this->date( $product->get_date_created() ),
			'date_modified'      => $this->date( $product->get_date_modified() ),

			'pricing' => [
				'price'         => $product->get_price(),
				'regular_price' => $product->get_regular_price(),
				'sale_price'    => $product->get_sale_price(),
				'on_sale'       => $product->is_on_sale(),
				'tax_status'    => $product->get_tax_status(),
				'tax_class'     => $product->get_tax_class(),
			],

			'inventory' => [
				'stock_status'     => $product->get_stock_status(),
				'manage_stock'     => $product->get_manage_stock(),
				'stock_quantity'   => $product->get_stock_quantity(),
				'backorders'       => $product->get_backorders(),
				'backorders_allowed' => $product->backorders_allowed(),
				'sold_individually'  => $product->get_sold_individually(),
			],

			'shipping' => [
				'weight'           => $product->get_weight(),
				'length'           => $product->get_length(),
				'width'            => $product->get_width(),
				'height'           => $product->get_height(),
				'shipping_class'   => $product->get_shipping_class(),
				'shipping_class_id' => $product->get_shipping_class_id(),
			],

			'image'  => $this->image( $product->get_image_id(), true ), // main image,
			'images' => $this->images( $product ),

			'categories' => $this->terms( $product->get_id(), 'product_cat' ),
			'tags'       => $this->terms( $product->get_id(), 'product_tag' ),

			'attributes' => $this->attributes( $product ),

			'children' => $product->is_type( 'variable' )
				? array_map( 'absint', $product->get_children() )
				: [],
		];

		$data = apply_filters(
			'wp_mcp_server_woocommerce_product_data',
			$data,
			$product,
			[
				'product_id'   => $product->get_id()
			]
		);
	}

	private function image( int $attachment_id, bool $include_binary = false ): ?array {
		if ( ! $attachment_id ) {
			return null;
		}

		$image = [
			'id'  => $attachment_id,
			'url' => wp_get_attachment_image_url( $attachment_id, 'full' ) ?: null,
			'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		];

		if ( $include_binary ) {
			$image['binary'] = $this->image_binary( $attachment_id, 'thumbnail' );
		}

		return $image;
	}

	private function image_binary( int $attachment_id, string $size = 'thumbnail' ): ?array {
		$file_path = $this->attachment_file_path_for_size( $attachment_id, $size );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return null;
		}

		$contents = file_get_contents( $file_path );

		if ( false === $contents ) {
			return null;
		}

		$mime_type = wp_check_filetype( $file_path )['type'] ?? get_post_mime_type( $attachment_id );

		return [
			'mime_type' => $mime_type ?: 'application/octet-stream',
			'base64'    => base64_encode( $contents ),
		];
	}

	/**
	 * Resolves the on-disk path for a given intermediate image size,
	 * falling back to the original file if that size doesn't exist
	 * (e.g. size wasn't generated, or image is smaller than the size).
	 */
	private function attachment_file_path_for_size( int $attachment_id, string $size ): ?string {
		$original_path = get_attached_file( $attachment_id );

		if ( ! $original_path ) {
			return null;
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( empty( $metadata['sizes'][ $size ]['file'] ) ) {
			return $original_path; // fallback: no thumbnail size generated
		}

		return path_join( dirname( $original_path ), $metadata['sizes'][ $size ]['file'] );
	}

	private function images( \WC_Product $product ): array {
		$image_ids = array_filter(
			array_merge(
				[ $product->get_image_id() ],
				$product->get_gallery_image_ids()
			)
		);

		return array_values(
			array_filter(
				array_map(
					fn( $image_id ) => $this->image( (int) $image_id ),
					$image_ids
				)
			)
		);
	}

	private function terms( int $product_id, string $taxonomy ): array {
		$terms = get_the_terms( $product_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		return array_map(
			static function ( $term ): array {
				return [
					'id'   => (int) $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				];
			},
			$terms
		);
	}

	private function attributes( \WC_Product $product ): array {
		$attributes = [];

		foreach ( $product->get_attributes() as $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$attributes[] = [
					'id'        => $attribute->get_id(),
					'name'      => $attribute->get_name(),
					'label'     => wc_attribute_label( $attribute->get_name() ),
					'visible'   => $attribute->get_visible(),
					'variation' => $attribute->get_variation(),
					'options'   => $this->attribute_options( $attribute ),
				];
			}
		}

		return $attributes;
	}

	private function attribute_options( \WC_Product_Attribute $attribute ): array {
		if ( $attribute->is_taxonomy() ) {
			$terms = $attribute->get_terms();

			return array_map(
				static function ( $term ): array {
					return [
						'id'   => (int) $term->term_id,
						'name' => $term->name,
						'slug' => $term->slug,
					];
				},
				$terms
			);
		}

		return array_values( $attribute->get_options() );
	}

	private function date( $date ): ?string {
		if ( ! $date instanceof \WC_DateTime ) {
			return null;
		}

		return $date->date( DATE_ATOM );
	}
	
	
	public static function summary_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [
				'id',
				'type',
				'name',
				'slug',
				'sku',
				'url',
				'price',
				'regular_price',
				'sale_price',
				'stock_status',
				'catalog_visibility',
				'image',
			],
			'properties' => [
				'id'                 => [ 'type' => 'integer' ],
				'type'               => [ 'type' => 'string' ],
				'name'               => [ 'type' => 'string' ],
				'slug'               => [ 'type' => 'string' ],
				'sku'                => [ 'type' => 'string' ],
				'url'                => [ 'type' => [ 'string', 'null' ] ],
				'price'              => [ 'type' => 'string' ],
				'regular_price'      => [ 'type' => 'string' ],
				'sale_price'         => [ 'type' => 'string' ],
				'stock_status'       => [ 'type' => 'string' ],
				'catalog_visibility' => [ 'type' => 'string' ],
				'image'              => self::image_schema(),
			],
		];
	}
	
	public static function full_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [
				'id',
				'type',
				'name',
				'slug',
				'sku',
				'url',
				'description',
				'short_description',
				'status',
				'featured',
				'catalog_visibility',
				'date_created',
				'date_modified',
				'pricing',
				'inventory',
				'shipping',
				'images',
				'categories',
				'tags',
				'attributes',
				'children',
			],
			'properties' => [
				'id'                 => [ 'type' => 'integer' ],
				'type'               => [ 'type' => 'string' ],
				'name'               => [ 'type' => 'string' ],
				'slug'               => [ 'type' => 'string' ],
				'sku'                => [ 'type' => 'string' ],
				'url'                => [ 'type' => [ 'string', 'null' ] ],

				'description'        => [ 'type' => 'string' ],
				'short_description'  => [ 'type' => 'string' ],

				'status'             => [ 'type' => 'string' ],
				'featured'           => [ 'type' => 'boolean' ],
				'catalog_visibility' => [ 'type' => 'string' ],

				'date_created'       => [
					'type'   => [ 'string', 'null' ],
					'format' => 'date-time',
				],

				'date_modified'      => [
					'type'   => [ 'string', 'null' ],
					'format' => 'date-time',
				],

				'pricing'            => self::pricing_schema(),
				'inventory'          => self::inventory_schema(),
				'shipping'           => self::shipping_schema(),

				'images'             => [
					'type'  => 'array',
					'items' => self::image_schema(),
				],

				'categories'         => [
					'type'  => 'array',
					'items' => self::term_schema(),
				],

				'tags'               => [
					'type'  => 'array',
					'items' => self::term_schema(),
				],

				'attributes'         => [
					'type'  => 'array',
					'items' => self::attribute_schema(),
				],

				'children'           => [
					'type'  => 'array',
					'items' => [
						'type' => 'integer',
					],
				],
			],
		];
	}
	
	
	public static function image_schema(): array {
		return [
			'type'       => [ 'object', 'null' ],
			'required'   => [ 'id', 'url', 'alt' ],
			'properties' => [
				'id'     => [ 'type' => 'integer' ],
				'url'    => [ 'type' => [ 'string', 'null' ] ],
				'alt'    => [ 'type' => 'string' ],
				'binary' => [
					'type'       => [ 'object', 'null' ],
					'properties' => [
						'mime_type' => [ 'type' => 'string' ],
						'base64'    => [ 'type' => 'string' ],
					],
				],
			],
		];
	}
	
	public static function pricing_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [
				'price',
				'regular_price',
				'sale_price',
				'on_sale',
				'tax_status',
				'tax_class',
			],
			'properties' => [
				'price'         => [ 'type' => 'string' ],
				'regular_price' => [ 'type' => 'string' ],
				'sale_price'    => [ 'type' => 'string' ],
				'on_sale'       => [ 'type' => 'boolean' ],
				'tax_status'    => [ 'type' => 'string' ],
				'tax_class'     => [ 'type' => 'string' ],
			],
		];
	}

	public static function inventory_schema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'stock_status'       => [ 'type' => 'string' ],
				'manage_stock'       => [ 'type' => 'boolean' ],
				'stock_quantity'     => [ 'type' => [ 'integer', 'null' ] ],
				'backorders'         => [ 'type' => 'string' ],
				'backorders_allowed' => [ 'type' => 'boolean' ],
				'sold_individually'  => [ 'type' => 'boolean' ],
			],
		];
	}

	public static function shipping_schema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'weight'            => [ 'type' => 'string' ],
				'length'            => [ 'type' => 'string' ],
				'width'             => [ 'type' => 'string' ],
				'height'            => [ 'type' => 'string' ],
				'shipping_class'    => [ 'type' => 'string' ],
				'shipping_class_id' => [ 'type' => 'integer' ],
			],
		];
	}

	public static function term_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'id', 'name', 'slug' ],
			'properties' => [
				'id'   => [ 'type' => 'integer' ],
				'name' => [ 'type' => 'string' ],
				'slug' => [ 'type' => 'string' ],
			],
		];
	}
	
	public static function attribute_schema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'id'        => [ 'type' => 'integer' ],
				'name'      => [ 'type' => 'string' ],
				'label'     => [ 'type' => 'string' ],
				'visible'   => [ 'type' => 'boolean' ],
				'variation' => [ 'type' => 'boolean' ],
				'options'   => [
					'type' => 'array',
				],
			],
		];
	}
	
	
	
}