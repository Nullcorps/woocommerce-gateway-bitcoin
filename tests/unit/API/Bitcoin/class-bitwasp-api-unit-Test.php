<?php

namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

use BrianHenryIE\ColorLogger\ColorLogger;
use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage;

/**
 * @coversDefaultClass \Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin\BitWasp_API
 */
class BitWasp_API_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::generate_address
	 * @covers ::__construct
	 */
	public function test_generate_addresses(): void {

		$logger          = new ColorLogger();
		$address_storage = $this->makeEmpty( Address_Storage::class );

		$sut = new BitWasp_API( $address_storage, $logger );

		$public_address = 'zpub6n37hVDJHFyDG1hBERbMBVjEd6ws6zVhg9bMs5STo21i9DgDE9Z9KTedtGxikpbkaucTzpj79n6Xg8Zwb9kY8bd9GyPh9WVRkM55uK7w97K';

		$result = $sut->generate_address( $public_address, 0 );

		$this->assertEquals( 'bc1qzs6ttahakr604009st6vzgkjzx670uwvnfldcn', $result );
	}

	/**
	 * The address for this test case was generated first in Electrum.
	 *
	 * @covers ::generate_address
	 */
	public function test_address_generated_by_electrum(): void {

		$logger          = new ColorLogger();
		$address_storage = $this->makeEmpty( Address_Storage::class );

		$sut = new BitWasp_API( $address_storage, $logger );

		$public_address = 'zpub6n37hVDJHFyDG1hBERbMBVjEd6ws6zVhg9bMs5STo21i9DgDE9Z9KTedtGxikpbkaucTzpj79n6Xg8Zwb9kY8bd9GyPh9WVRkM55uK7w97K';

		$result = $sut->generate_address( $public_address, 1 );

		$this->assertEquals( 'bc1qgvm4wf9c79e59vsw0t8x6peqrdmxr3dju9jv8e', $result );
	}

}
