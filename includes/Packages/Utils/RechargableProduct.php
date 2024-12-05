<?php

namespace SAIL\Packages\Utils;

use WC_Product;
use WC_Product_Simple;

class RechargableProduct {

	const option_name = 'sail_rechargable_product';

	public function create_product(){
		$product = new WC_Product_Simple;
		$product->set_name(self::option_name);
		$product->set_status('private');
		$product->set_price(0);
		$product->set_sold_individually(false);
		$product->set_backorders(false);
		$product->set_downloadable(false);
		$product->set_virtual(true);
		$product->set_manage_stock(false);
		$product->set_reviews_allowed(false );
		$product->set_catalog_visibility( 'hidden' );
		$product->save();
		$product_id = $product->get_id();
		update_option(self::option_name,$product_id);
		return $product;
	}

	public function get_product(){
		$product_id = get_option(self::option_name);
		return wc_get_product($product_id) instanceof WC_Product ? wc_get_product($product_id) : $this->create_product();
	}

}
