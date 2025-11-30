<?php
/**
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Model\Transaction_Interface;
use BrianHenryIE\WP_Bitcoin_Gateway\Brick\Money\Money;
use DateTimeInterface;
use Exception;
use BrianHenryIE\WP_Bitcoin_Gateway\Admin\Addresses_List_Table;
use BrianHenryIE\WP_Bitcoin_Gateway\Integrations\WooCommerce\Bitcoin_Gateway;
use RuntimeException;
use InvalidArgumentException;
use WP_Post;

enum Bitcoin_Address_Status: string {

	case UNKNOWN  = 'unknown';
	case UNUSED   = 'unused';
	case ASSIGNED = 'assigned';
	case USED     = 'used';

	// inherent WordPress status
	case DRAFT = 'draft';
}
