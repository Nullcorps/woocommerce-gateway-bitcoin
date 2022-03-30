<?php
/**
 * The local PHP library for generating addresses.
 *
 * @package    nullcorps/woocommerce-gateway-bitcoin
 */

namespace Nullcorps\WC_Gateway_Bitcoin\API\Bitcoin;

use Nullcorps\WC_Gateway_Bitcoin\API\Address_Storage;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Address\AddressCreator;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Bitcoin;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Network\NetworkFactory;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class BitWasp_API implements Generate_Address_API_Interface {
	use LoggerAwareTrait;

	protected Address_Storage $address_storage;

	public function __construct( Address_Storage $address_storage, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->address_storage = $address_storage;
	}

	/**
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\InvalidNetworkParameter
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\RandomBytesFailure
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Buffertools\Exceptions\ParserOutOfRange
	 * @throws \Nullcorps\WC_Gateway_Bitcoin\BitWasp\Bitcoin\Exceptions\DisallowedScriptDataFactoryException
	 * @throws \Exception
	 */
	public function generate_address( string $public_address, int $nth ): string {

		$path = "0/$nth";

		// https://gist.github.com/mariodian/5b67a1f315a74a7753a6f23d0198ec48
		$adapter          = Bitcoin::getEcAdapter();
		$helper           = new KeyToScriptHelper( $adapter );
		$slip132          = new Slip132( $helper );
		$bitcoin_prefixes = new BitcoinRegistry();

		switch ( substr( $public_address, 0, 4 ) ) {
			case 'xpub':
				$pub_prefix = $slip132->p2pkh( $bitcoin_prefixes );
				break;
			case 'ypub':
				$pub_prefix = $slip132->p2shP2wpkh( $bitcoin_prefixes );
				break;
			case 'zpub':
				$pub_prefix = $slip132->p2wpkh( $bitcoin_prefixes );
				break;
			default:
				throw new \Exception( 'Bad public key' );
		}

		$network = NetworkFactory::bitcoin();

		$network_config = new NetworkConfig( $network, array( $pub_prefix ) );
		$config         = new GlobalPrefixConfig( array( $network_config ) );

		$serializer = new Base58ExtendedKeySerializer(
			new ExtendedKeySerializer( $adapter, $config )
		);

		$key       = $serializer->parse( $network, $public_address );
		$child_key = $key->derivePath( $path );

		return $child_key->getAddress( new AddressCreator() )->getAddress();
	}

}
