<?php
/**
 * Plugin Name: MetaMedia
 * Plugin URI:  https://github.com/Maximoo/metamedia
 * Description: Parsea 'post_content' y guarda un array ["metamedia"] con los elementos multimedia encontrados en cada post
 * Version:     1.0
 * Author:      Maximo_o
 * Author URI:  https://github.com/Maximoo
 * Donate link: https://github.com/Maximoo
 * License:     GPLv3
 * Text Domain: metamedia
 *
 * @link https://github.com/Maximoo
 *
 * @package MetaMedia
 * @version 1.0
 */

/**
 * Copyright (c) 2016 Maximo_o (email : deluzmax@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

class MetaMedia {

	public function __construct() {
		add_action( 'save_post', array( $this, 'parse_post_content' ) );
	}

  public function parse_post_content( $post_id ) {
    $post_content = get_post_field('post_content', $post_id);
    $metamedia = array();
    $this->merge_matches($metamedia, 'youtube', $this->get_youtube_matches($post_content));
    $this->merge_matches($metamedia, 'vimeo', $this->get_vimeo_matches($post_content));
    $this->merge_matches($metamedia, 'jwplayer', $this->get_shortcode_matches('jwplayer', $post_content));
    update_post_meta($post_id, 'metamedia', $metamedia);
  }

  protected function merge_matches( &$stack, $key, $media ){
    if(!empty($media)){
      $stack[$key] = array();
      for($i = 0; $i < count($media[0]); $i++){
        $stack[$key][] = array("media" => trim($media[0][$i]), "query" => trim($media[1][$i]));
      }
    }
  }

  public function get_youtube_matches( $content ) {
      preg_match_all('~(?#!js YouTubeId Rev:20160125_1800)
        # Match non-linked youtube URL in the wild. (Rev:20130823)
        https?://          # Required scheme. Either http or https.
        (?:[0-9A-Z-]+\.)?  # Optional subdomain.
        (?:                # Group host alternatives.
          youtu\.be/       # Either youtu.be,
        | youtube          # or youtube.com or
          (?:-nocookie)?   # youtube-nocookie.com
          \.com            # followed by
          \S*?             # Allow anything up to VIDEO_ID,
          [^\w\s-]         # but char before ID is non-ID char.
        )                  # End host alternatives.
        ([\w-]{11})        # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w-]|$)       # Assert next char is non-ID or EOS.
        (?!                # Assert URL is not pre-linked.
          [?=&+%\w.-]*     # Allow URL (query) remainder.
          (?:              # Group pre-linked alternatives.
            [\'"][^<>]*>   # Either inside a start tag,
          | </a>           # or inside <a> element text contents.
          )                # End recognized pre-linked alts.
        )                  # End negative lookahead assertion.
        [?=&+%\w.-]*       # Consume any URL (query) remainder.
        ~ix', $content, $matches);
    return !empty($matches[0]) ? $matches : false;
  }

  public function get_vimeo_matches( $content ){
    preg_match_all("#https?://(?:player\.|www\.)?vimeo\.com/(\w*/)*(([a-z]{0,2}-)?\d+)#", $content, $matches);
    return !empty($matches[0]) ? array($matches[0],$matches[2]) : false;
  }

  public function get_shortcode_matches( $shortcode, $content ){
    preg_match_all( '/'. get_shortcode_regex(array($shortcode)) .'/s', $content, $matches );
    return array_key_exists( 2, $matches ) && in_array( $shortcode, $matches[2] ) ? array($matches[0],$matches[3]) : false;
  }

};

add_action(
	'plugins_loaded', function () {
		global $MetaMedia;
		$MetaMedia = new MetaMedia();
	}
);
