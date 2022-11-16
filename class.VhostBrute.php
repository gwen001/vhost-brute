<?php

class VhostBrute
{
	const T_HEADER_EXCLUDE = [ 'date', 'location', 'server', 'x-connection-hash', 'x-response-time', 'x-served-by' ];
	const DEFAULT_SIMILAR_TEXT_CONFIRM = 90;

	private $reference = null;
	private $reference_random = null;


	private $domain = null;

	public function getDomain() {
		return $this->domain;
	}
	public function setDomain( $v ) {
		$this->domain = trim( $v );
		return true;
	}


	private $ip = null;
	private $_ip = null;

	public function getIp() {
		return $this->ip;
	}
	public function setIp( $v ) {
		$f = trim( $v );
		$this->ip = $f;
		if( is_file($this->ip) ) {
			$this->_ip = file( $this->ip, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			//sort( $this->_ip );
		} else {
			$this->_ip = [ $this->ip ];
		}
		return true;
	}


	private $n_fail = 0;
	private $max_fail = -1;

	public function getMaxFail() {
		return $this->max_fail;
	}
	public function setMaxFail( $v ) {
		$this->max_fail = (int)$v;
		return true;
	}


	private $similar_text_confirm = self::DEFAULT_SIMILAR_TEXT_CONFIRM;

	public function getSimilarTextConfirm() {
		return $this->similar_text_confirm;
	}
	public function setSimilarTextConfirm( $v ) {
		$this->similar_text_confirm = (int)$v;
		return true;
	}


	private $port = 80;

	public function getPort() {
		return $this->port;
	}
	public function setPort( $v ) {
		$this->port = (int)$v;
		return true;
	}


	private $ssl = false;

	public function getSsl() {
		return $this->ssl;
	}
	public function forceSsl( $v ) {
		$this->ssl = (bool)$v;
		return true;
	}

	private $max_child = 1;
	private $n_child = 0;
	private $loop_sleep = 100000;
	private $t_process = [];
	private $t_signal_queue = [];

	public function getMaxThreads() {
		return $this->max_child;
	}
	public function setMaxThreads( $v ) {
		$this->max_child = (int)$v;
		return true;
	}


	private $wordlist = null;
	private $_wordlist = [];
	private $_wordlists = [];
	private $n_words = 0;

	public function getWordlist() {
		return $this->wordlist;
	}
	public function setWordlist( $v ) {
		$f = trim( $v );
		$this->wordlist = $f;
		if( is_file($f) ) {
			$this->_wordlist = file( $this->wordlist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			sort( $this->_wordlist );
		} else {
			$this->_wordlist = [ $this->wordlist ];
		}
		return true;
	}


	private function print_recap()
	{
		echo str_pad( '', 100, '-' )."\n";
		echo "IP: ".$this->ip.' ('.$this->n_ips.')'."\n";
		echo "Domain: ".$this->domain."\n";
		echo "Port: ".$this->port."\n";
		echo "Wordlist: ".$this->wordlist.' ('.$this->n_words.')'."\n";
		echo "Total count: ".$this->total_count."\n";
		echo "Threads: ".$this->max_child."\n";
		echo "Max fail: ".$this->max_fail." ".(($this->max_fail<0)?'(unlimited)':'')."\n";
		echo str_pad( '', 100, '-' )."\n\n";
	}


	public function run()
	{
		$this->n_ips = count( $this->_ip );
		$this->n_words = count( $this->_wordlist );
		$this->total_count = $this->n_ips * $this->n_words;

		if( $this->max_child <= 0 ) {
			$this->max_child = 1;
		}
		if( $this->max_child > $this->total_count ) {
			$this->max_child = $this->total_count;
		}

		for( $i=0,$j=0 ; $i<$this->n_ips ; $i++ ) {
			for( $w=0 ; $w<$this->n_words ; $w++,$j++ ) {
				$thread = $j % $this->max_child;
				if( !isset($this->_wordlists[$thread]) ) {
					$this->_wordlists[$thread] = [];
				}
				$this->_wordlists[$thread][] = [ $this->_ip[$i], $this->_wordlist[$w] ];
			}
		}

		$this->print_recap();

		//echo str_pad( '', 100, '-' )."\n";
		$this->reference = $this->doRequest( $this->_ip[0], '' );
		$this->printResult( $this->_ip[0], '', $this->reference );

		if( $this->reference->getResultCode() == 0 ) {
			echo "\nIP seems to be KO, exiting...\n";
			exit();
		}

		//$this->reference_random = $this->doRequest( ($h=uniqid('')) );
		//$this->printResult( $h, $this->reference_random, true );
		$this->reference_random_domain = $this->doRequest( $this->_ip[0], ($h=uniqid('').'.'.$this->domain) );
		$this->printResult( $this->_ip[0], $h, $this->reference_random_domain, true );

		if( $this->reference_random_domain->getResultCode() == 0 ) {
			echo "\nIP seems to be KO, exiting...\n";
			exit();
		}

		echo "\n".str_pad( '', 100, '-' )."\n\n";
		//echo str_pad( '', 100, '-' )."\n\n";
		//exit();

		posix_setsid();
		declare( ticks=1 );
		pcntl_signal( SIGCHLD, array($this,'signal_handler') );

		for( $windex=0 ; $windex<$this->max_child ; $windex++ )
		{
			$pid = pcntl_fork();

			if( $pid == -1 ) {
				// fork error
			} elseif( $pid ) {
				// father
				$this->n_child++;
				$this->t_process[$pid] = uniqid();
				if( isset($this->t_signal_queue[$pid]) ){
					$this->signal_handler( SIGCHLD, $pid, $this->t_signal_queue[$pid] );
					unset( $this->t_signal_queue[$pid] );
				}
			} else {
				// child process
				$this->testWordlist( $windex );
				exit( 0 );
			}

			usleep( $this->loop_sleep );
		}

		while( $this->n_child ) {
			// surely leave the loop please :)
			sleep( 1 );
		}
	}


	private function testWordlist( $windex )
	{
		foreach( $this->_wordlists[$windex] as $w )
		{
			ob_start();
			$ip = $w[0];
			$host = $w[1] . '.' . $this->domain;
			$request = $this->doRequest( $ip, $host );

			if( $request->getResultCode() == 0 ) {
				$this->n_fail++;
				if( $this->n_fail >= $this->max_fail && $this->max_fail!=-1 ) {
					echo "\nToo many KOs ".$this->n_fail.", exiting...\n";
					exit();
				}
			}

			//var_dump( $request->getResultHeader() );
			$this->printResult( $ip, $host, $request, true );
			$result = ob_get_contents();
			ob_end_clean();
			echo $result;
		}
	}


	private function doRequest( $ip, $host )
	{
		$request = new HttpRequest();
		$request->setRedirect( false );
		$request->setSsl( $this->ssl );
		$request->setUrl( $ip );
		if( $host != '' ) {
			$request->setHeader( $host, 'Host' );
		}
		if( $this->port && $this->port!=80 && $this->port!=443 ) {
			$request->setPort( $this->port );
		}
		$request->request();

		return $request;
	}


	private function printResult( $ip, $host, $request, $compare=false )
	{
		$color = 'white';
		$display_diff_header = false;

		$output = $ip;
		if( $this->port ) {
			$output .= ':'.$this->port;
		}
		$output .= "\t\t".$host;
		$output .= "\t\tC=".$request->getResultCode();
		$output .= "\t\tL=".$request->getResultBodySize();
		//$output .= "\t\tH:";

		if( !$compare ) {
			Utils::_println( $output, $color );
			return ;
		}

		$st = similar_text( $this->reference_random_domain->getResultBody(), $request->getResultBody(), $similar_text );
		$similar_text = (int)$similar_text;
		$output .= "\t\tST=".$similar_text.'%';

		$diff_header = null;
		$t_compare = null;
		if( $request->getResultCode() ) {
			$diff_header = $this->compareHeaders( $this->reference_random_domain, $request, $t_compare );
		}

		if( !$request->getResultCode() ) {
			$color = 'light_grey';
			$output .= "\t\tKO";
		}
		elseif( $request->getResultCode() != $this->reference_random_domain->getResultCode() || ($similar_text <= $this->similar_text_confirm && $similar_text != 0) ) {
			$color = 'light_green';
			$output .= "\t\tFOUND";
			$display_diff_header = true;
		} elseif( $request->getResultBodySize() != $this->reference_random_domain->getResultBodySize() || $diff_header ) {
			$color = 'yellow';
			$output .= "\t\tWARNING";
			$display_diff_header = true;
		} else {
			$color = 'white';
			$output .= "\t\tNOTHING";
		}

		Utils::_println( $output, $color );

		$t_result_colors = [ 3=>'light_grey', 2=>'blue', 0=>'red', 1=>'light_purple' ];

		if( $display_diff_header && $t_compare ) {
			foreach( $t_result_colors as $i=>$c ) {
				foreach( $t_compare[$i] as $k=>$v ) {
					Utils::_println( "\t- ".$k.': '.$v, $c );
				}
			}
			/*
			foreach( $t_compare[3] as $k=>$v ) {
				Utils::_println( "\t= ".$k.': '.$v, 'light_grey' );
			}
			foreach( $t_compare[2] as $k=>$v ) {
				Utils::_println( "\t~ ".$k.': '.$v, 'blue' );
			}
			foreach( $t_compare[0] as $k=>$v ) {
				Utils::_println( "\t- ".$k.': '.$v, 'red' );
			}
			foreach( $t_compare[1] as $k=>$v ) {
				Utils::_println( "\t+ ".$k.': '.$v, 'green' );
			}*/
		}
	}


	private function compareHeaders( $reference, $request, &$t_compare )
	{
		$h1 = $reference->getResultHeader();
		$h2 = $request->getResultHeader();
		$t_compare = [ 0=>[], 1=>[], 2=>[], 3=>[] ];

		// 0 deleted headers
		// 1 extra headers
		// 2 altered headers
		// 3 same headers

		foreach( self::T_HEADER_EXCLUDE as $k ) {
			foreach( $h1 as $k1=>$v1 ) {
				if( strcasecmp($k,$k1) == 0 ) {
					unset( $h1[$k1] );
				}
			}
			foreach( $h2 as $k2=>$v2 ) {
				if( strcasecmp($k,$k2) == 0 ) {
					unset( $h2[$k2] );
				}
			}
		}

		$t_compare[0] = array_diff_key( $h1, $h2 );
		$t_compare[1] = array_diff_key( $h2, $h1 );

		foreach( $h1 as $k=>$v ) {
			if( isset($h2[$k]) ) {
				if( $h1[$k] != $h2[$k] ) {
					$t_compare[2][$k] = $h1[$k] . '  ->  ' . $h2[$k];
				} else {
					$t_compare[3][$k] = $v;
				}
			}
		}

		return count($t_compare[0])+count($t_compare[1]);
		//return count($t_compare[0])+count($t_compare[1])+count($t_compare[2]);
	}


	// http://stackoverflow.com/questions/16238510/pcntl-fork-results-in-defunct-parent-process
	// Thousand Thanks!
	public function signal_handler( $signal, $pid=null, $status=null )
	{
		$pid = (int)$pid;

		// If no pid is provided, Let's wait to figure out which child process ended
		if( !$pid ){
			$pid = pcntl_waitpid( -1, $status, WNOHANG );
		}

		// Get all exited children
		while( $pid > 0 )
		{
			if( $pid && isset($this->t_process[$pid]) ) {
				// I don't care about exit status right now.
				//  $exitCode = pcntl_wexitstatus($status);
				//  if($exitCode != 0){
				//      echo "$pid exited with status ".$exitCode."\n";
				//  }
				// Process is finished, so remove it from the list.
				$this->n_child--;
				unset( $this->t_process[$pid] );
			}
			elseif( $pid ) {
				// Job finished before the parent process could record it as launched.
				// Store it to handle when the parent process is ready
				$this->t_signal_queue[$pid] = $status;
			}

			$pid = pcntl_waitpid( -1, $status, WNOHANG );
		}

		return true;
	}
}
