<?php
/**
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses;

enum Bitcoin_Address_Status: string {

	case UNKNOWN  = 'unknown';
	case UNUSED   = 'unused';
	case ASSIGNED = 'assigned';
	case USED     = 'used';

	// Inherent WordPress status.
	case DRAFT   = 'draft';
	case PUBLISH = 'publish';
	case ALL     = 'all';
}
