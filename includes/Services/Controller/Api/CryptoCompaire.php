<?php

namespace SAIL\Services\Controller\Api;

use GuzzleHttp\Client;
use SAIL\Packages\Contracts\Controller;

class CryptoCompaire extends Controller{

	public function log(){
		$this->logger->warning('Foo');
		$this->logger->notice('notice');
		$this->logger->emergency('emergency');
		$this->logger->info('cron was updated', ['time' => time()]);
        $this->logger->warning('Foo with context', [
            'name' => 'Sarah',
            'age'  => '23',
        ]);
	}

	public function request(){
        $client = new Client([
            'base_uri' => 'http://nerkh-api.ir/api/APITOKEN/',
            'timeout'  => 2.0,
        ]);
        $currency = $client->get('currency')->getBody();
        $gold = $client->get('gold')->getBody();
		return [
			'nerkh' => [
				'gold' => json_decode($gold),
				'currency' => json_decode($currency),
			]
		];
	}
}
