<?php
/**
 * Functions implemented by API class, required by Background_Jobs class
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\Action_Scheduler;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses\Bitcoin_Address;
use BrianHenryIE\WP_Bitcoin_Gateway\API\Blockchain\Rate_Limit_Exception;

interface API_Background_Jobs_Interface {

	public function generate_new_addresses(): array;

	/**
	 * This should just be {@see ::update_address_transactions} but the post status would go from new to used rather than from assigned to completed.
	 *
	 * @throws Rate_Limit_Exception When the remote API refuses too many requests.
	 */
	public function check_new_addresses_for_transactions(): array;

	public function update_address_transactions( Bitcoin_Address $address ): array;
}
