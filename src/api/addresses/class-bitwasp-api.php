<?php
/**
 * The local PHP library for generating addresses.
 *
 * @see https://github.com/Bit-Wasp/bitcoin-php
 * @see https://gist.github.com/mariodian/5b67a1f315a74a7753a6f23d0198ec48
 *
 * @package    brianhenryie/bh-wp-bitcoin-gateway
 */

namespace BrianHenryIE\WP_Bitcoin_Gateway\API\Addresses;

use BrianHenryIE\WP_Bitcoin_Gateway\API\Generate_Address_API_Interface;
use Exception;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Address\AddressCreator;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Bitcoin;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Network\NetworkFactory;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BrianHenryIE\WP_Bitcoin_Gateway\BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BrianHenryIE\WP_Bitcoin_Gateway\Psr\Log\LoggerAwareTrait;
use BrianHenryIE\WP_Bitcoin_Gateway\Psr\Log\LoggerInterface;

/**
 * Use Bitwasp API to generate public addresses.
 */
class BitWasp_API implements Generate_Address_API_Interface {
	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger PSR Logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * Generate the nth address for the given xpub|ypub|zpub.
	 *
	 * @param string $public_address The wallet address.
	 * @param int    $nth Derive path nth address in sequence.
	 *
	 * @return string
	 * @throws Exception Failed to generate address.
	 */
	public function generate_address( string $public_address, int $nth ): string {

		$path = "0/$nth";

		$adapter          = Bitcoin::getEcAdapter();
		$helper           = new KeyToScriptHelper( $adapter );
		$slip132          = new Slip132( $helper );
		$bitcoin_prefixes = new BitcoinRegistry();

		switch ( substr( $public_address, 0, 4 ) ) {
			case 'xpub':
				/**
				 * Pay-to-Pubkey Hash.
				 *
				 * TODO: This also applies to 'tpub'?
				 *
				 * @see https://en.bitcoinwiki.org/wiki/Pay-to-Pubkey_Hash
				 */
				$pub_prefix = $slip132->p2pkh( $bitcoin_prefixes );
				break;
			case 'ypub':
				/**
				 * TODO: Pay To Script Hash - Pay to Witness Script Hash ?
				 */
				$pub_prefix = $slip132->p2shP2wpkh( $bitcoin_prefixes );
				break;
			case 'zpub':
				/**
				 * Pay to Witness Public Key Hash.
				 *
				 * TODO: This also applies to 'vpub'?
				 *
				 * @see https://programmingblockchain.gitbook.io/programmingblockchain/other_types_of_ownership/p2wpkh_pay_to_witness_public_key_hash
				 */
				$pub_prefix = $slip132->p2wpkh( $bitcoin_prefixes );
				break;
			default:
				throw new Exception( 'Bad public key' );
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
