<?php

namespace WP_MCP_Server\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooReportService {

	public function product_status_summary(): array {
		global $wpdb;

		$post_types = [ 'product', 'product_variation' ];

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT post_status, post_type, COUNT(ID) AS total
				FROM {$wpdb->posts}
				WHERE post_type IN ({$placeholders})
				GROUP BY post_status, post_type
				ORDER BY post_type ASC, post_status ASC
				",
				...$post_types
			),
			ARRAY_A
		);

		$summary = [
			'products'   => [],
			'variations' => [],
			'total'      => 0,
		];

		foreach ( $rows as $row ) {
			$status = (string) $row['post_status'];
			$total  = (int) $row['total'];

			if ( 'product_variation' === $row['post_type'] ) {
				$summary['variations'][ $status ] = $total;
			} else {
				$summary['products'][ $status ] = $total;
			}

			$summary['total'] += $total;
		}

		return $summary;
	}

	public function order_status_summary(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'wc_order_stats';

		if ( ! $this->table_exists( $table ) ) {
			return $this->legacy_order_status_summary();
		}

		$rows = $wpdb->get_results(
			"
			SELECT status, COUNT(order_id) AS total
			FROM {$table}
			GROUP BY status
			ORDER BY status ASC
			",
			ARRAY_A
		);

		$summary = [
			'statuses' => [],
			'total'    => 0,
		];

		foreach ( $rows as $row ) {
			$status = (string) $row['status'];
			$total  = (int) $row['total'];

			$summary['statuses'][ $status ] = $total;
			$summary['total'] += $total;
		}

		return $summary;
	}

	public function order_report( array $args = [] ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'wc_order_stats';

		if ( ! $this->table_exists( $table ) ) {
			return [
				'error' => 'WooCommerce order analytics table not found.',
			];
		}

		$date_from = $this->sanitize_date( $args['date_from'] ?? gmdate( 'Y-m-01' ) );
		$date_to   = $this->sanitize_date( $args['date_to'] ?? gmdate( 'Y-m-d' ) );
		$group_by  = $this->sanitize_group_by( $args['group_by'] ?? 'month' );
		$statuses  = $this->sanitize_statuses( $args['statuses'] ?? [] );

		$date_format = 'day' === $group_by ? '%Y-%m-%d' : '%Y-%m';

		$where  = 'WHERE date_created >= %s AND date_created < DATE_ADD(%s, INTERVAL 1 DAY)';
		$params = [ $date_from, $date_to ];

		if ( ! empty( $statuses ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
			$where       .= " AND status IN ({$placeholders})";
			$params       = array_merge( $params, $statuses );
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT
					DATE_FORMAT(date_created, '{$date_format}') AS period,
					COUNT(order_id) AS orders,
					SUM(num_items_sold) AS items_sold,
					SUM(total_sales) AS gross_sales,
					SUM(net_total) AS net_sales,
					SUM(tax_total) AS tax,
					SUM(shipping_total) AS shipping
				FROM {$table}
				{$where}
				GROUP BY period
				ORDER BY period ASC
				",
				...$params
			),
			ARRAY_A
		);

		$totals = [
			'orders'              => 0,
			'items_sold'          => 0,
			'gross_sales'         => 0.0,
			'net_sales'           => 0.0,
			'tax'                 => 0.0,
			'shipping'            => 0.0,
			'average_order_value' => 0.0,
		];

		foreach ( $rows as &$row ) {
			$row['orders']      = (int) $row['orders'];
			$row['items_sold']  = (int) $row['items_sold'];
			$row['gross_sales'] = (float) $row['gross_sales'];
			$row['net_sales']   = (float) $row['net_sales'];
			$row['tax']         = (float) $row['tax'];
			$row['shipping']    = (float) $row['shipping'];

			$totals['orders']      += $row['orders'];
			$totals['items_sold']  += $row['items_sold'];
			$totals['gross_sales'] += $row['gross_sales'];
			$totals['net_sales']   += $row['net_sales'];
			$totals['tax']         += $row['tax'];
			$totals['shipping']    += $row['shipping'];
		}

		if ( $totals['orders'] > 0 ) {
			$totals['average_order_value'] = round(
				$totals['gross_sales'] / $totals['orders'],
				2
			);
		}

		return [
			'period' => [
				'from'     => $date_from,
				'to'       => $date_to,
				'group_by' => $group_by,
				'statuses' => $statuses,
			],
			'totals' => $totals,
			'rows'   => $rows,
		];
	}

	private function legacy_order_status_summary(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"
			SELECT post_status AS status, COUNT(ID) AS total
			FROM {$wpdb->posts}
			WHERE post_type = 'shop_order'
			GROUP BY post_status
			ORDER BY post_status ASC
			",
			ARRAY_A
		);

		$summary = [
			'statuses' => [],
			'total'    => 0,
		];

		foreach ( $rows as $row ) {
			$status = (string) $row['status'];
			$total  = (int) $row['total'];

			$summary['statuses'][ $status ] = $total;
			$summary['total'] += $total;
		}

		return $summary;
	}

	private function table_exists( string $table ): bool {
		global $wpdb;

		return $table === $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);
	}

	private function sanitize_date( mixed $date ): string {
		$date = sanitize_text_field( (string) $date );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return gmdate( 'Y-m-d' );
		}

		return $date;
	}

	private function sanitize_group_by( mixed $group_by ): string {
		$group_by = sanitize_key( (string) $group_by );

		return in_array( $group_by, [ 'day', 'month' ], true ) ? $group_by : 'month';
	}

	private function sanitize_statuses( mixed $statuses ): array {
		if ( ! is_array( $statuses ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_key', $statuses )
			)
		);
	}
}