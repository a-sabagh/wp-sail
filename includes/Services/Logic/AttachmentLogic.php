<?php

namespace SAIL\Services\Logic;

use WP_Post;
use SAIL\Packages\Contracts\Controller;

class AttachmentLogic extends Controller {

	public function prepare_attachments_data(array $attachment_ids,string $size='full'){
		if(empty($attachment_ids)){
			return [];
		}
		foreach($attachment_ids as $id){
			$data[] = $this->prepare_attachment_data($id,$size);
		}
		return $data;
	}

	public function prepare_attachment_data(int $attachment_id,string $size='full'){
		return [
			'id' => $attachment_id,
			'url' => wp_get_attachment_url($attachment_id,$size),
			'alt' => get_post_meta($attachment_id,'_wp_attachment_image_alt',true),
		];
	}

	public function prepare_attachments_meta_data(array $attachment_ids){
		if(!empty($attachment_ids)){
			return [];
		}
		foreach($attachment_ids as $id){
			$attachment_post = get_post($id);
			if(false == $attachment_post instanceof WP_Post){
				continue;
			}
			$meta_data[] = [
				'caption' => $attachment_post->post_excerpt,
				'description' => $attachment_post->post_content,
				'href' => get_permalink( $attachment_post->ID ),
				'src' => $attachment_post->guid,
				'title' => $attachment_post->post_title,
			];
		}
		return $meta_data ?? [];
	}

}
