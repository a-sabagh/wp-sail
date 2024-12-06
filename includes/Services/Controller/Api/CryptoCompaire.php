<?php

namespace SAIL\Services\Controller\Api;

use SAIL\Packages\Contracts\Controller;

class CryptoCompaire extends Controller{

	public function request(){
		$this->logger->warning('Foo');
		$this->logger->notice('notice');
		$this->logger->emergency('emergency');
		$this->logger->info('cron was updated', ['time' => time()]);
        $this->logger->warning('Foo with context', [
            'name' => 'Sarah',
            'age'  => '23',
        ]);
	}

}
