#!/usr/bin/php
<?php

spl_autoload_register(function ( $c ) {
	include( dirname(__FILE__).'/class.'.$c.'.php' );
});


// parse command line
{
	$options = [
		'domain:',
		'fail:',
		'help',
		'ip:',
		'port:',
		'ssl',
		'st:',
		'threads:',
		'wordlist:',
	];
	$t_options = getopt( '', $options );
	//var_dump( $t_options );

	$vbrute = new VhostBrute();

	foreach( $t_options as $k=>$v ) {
		switch( $k ) {
			case 'domain':
				$vbrute->setDomain( $v );
				break;

			case 'fail':
				$vbrute->setMaxFail( $v );
				break;

			case '-h':
			case 'help':
				Utils::help();
				break;

			case 'ip':
				$vbrute->setIp( $v );
				break;

			case 'ssl':
				$vbrute->forceSsl( true );
				break;

			case 'st':
				$vbrute->setSimilarTextConfirm( $v );
				break;

			case 'port':
				$vbrute->setPort( $v );
				break;

			case 'threads':
				$vbrute->setMaxThreads( $v );
				break;

			case 'wordlist':
				$vbrute->setWordlist( $v );
				break;

			default:
				Utils::help( 'Unknown option: '.$k );
		}
	}

	if( !$vbrute->getWordlist() ) {
		Utils::help('Wordlist not found!');
	}
	if( !$vbrute->getIp() ) {
		Utils::help('IP not found!');
	}
	if( !$vbrute->getDomain() ) {
		Utils::help('Domain not found!');
	}
}
// ---


// main loop
{
	echo "\n";
	$vbrute->run();
	echo "\n".str_pad( '', 100, '-' )."\n\n";
}
// ---


exit();
