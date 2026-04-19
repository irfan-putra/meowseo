<?php
/**
 * Video Detector
 *
 * @package MeowSEO
 */

namespace MeowSEO\Modules\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Video_Detector class
 *
 * Parses post content to identify embedded YouTube and Vimeo videos.
 * Supports both Gutenberg blocks and classic editor content.
 *
 * @since 1.0.0
 */
class Video_Detector {
	/**
	 * YouTube URL patterns
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $youtube_patterns = array(
		// Standard watch URL
		'#https?://(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})#',
		// Short URL
		'#https?://youtu\.be/([a-zA-Z0-9_-]{11})#',
		// Embed URL
		'#https?://(?:www\.)?youtube\.com/embed/([a-zA-Z0-9_-]{11})#',
	);

	/**
	 * Vimeo URL patterns
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $vimeo_patterns = array(
		// Standard URL
		'#https?://(?:www\.)?vimeo\.com/(\d+)#',
		// Player URL
		'#https?://player\.vimeo\.com/video/(\d+)#',
	);

	/**
	 * Detect videos in content
	 *
	 * Parses post content to identify all embedded YouTube and Vimeo videos.
	 * Automatically detects whether content is Gutenberg blocks or classic editor.
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 * @return array Array of detected videos with platform and ID.
	 *               Format: [['platform' => 'youtube', 'id' => 'abc123'], ...]
	 */
	public function detect_videos( string $content ): array {
		$videos = array();

		// Check if content contains Gutenberg blocks
		if ( has_blocks( $content ) ) {
			$videos = $this->parse_gutenberg_blocks( $content );
		} else {
			$videos = $this->parse_classic_editor_content( $content );
		}

		// Remove duplicates based on platform + ID combination
		$unique_videos = array();
		$seen = array();

		foreach ( $videos as $video ) {
			$key = $video['platform'] . ':' . $video['id'];
			if ( ! isset( $seen[ $key ] ) ) {
				$unique_videos[] = $video;
				$seen[ $key ] = true;
			}
		}

		return $unique_videos;
	}

	/**
	 * Detect YouTube videos
	 *
	 * Extracts all YouTube video IDs from content using regex patterns.
	 * Supports standard, short, and embed URL formats.
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 * @return array Array of YouTube video IDs.
	 */
	public function detect_youtube_videos( string $content ): array {
		$video_ids = array();

		foreach ( $this->youtube_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				$video_ids = array_merge( $video_ids, $matches[1] );
			}
		}

		// Remove duplicates
		return array_unique( $video_ids );
	}

	/**
	 * Detect Vimeo videos
	 *
	 * Extracts all Vimeo video IDs from content using regex patterns.
	 * Supports standard and player URL formats.
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 * @return array Array of Vimeo video IDs.
	 */
	public function detect_vimeo_videos( string $content ): array {
		$video_ids = array();

		foreach ( $this->vimeo_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				$video_ids = array_merge( $video_ids, $matches[1] );
			}
		}

		// Remove duplicates
		return array_unique( $video_ids );
	}

	/**
	 * Extract YouTube ID from URL
	 *
	 * Extracts the video ID from various YouTube URL formats.
	 *
	 * @since 1.0.0
	 * @param string $url YouTube URL.
	 * @return string|false Video ID or false if not found.
	 */
	private function extract_youtube_id( string $url ) {
		foreach ( $this->youtube_patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}
		return false;
	}

	/**
	 * Extract Vimeo ID from URL
	 *
	 * Extracts the video ID from various Vimeo URL formats.
	 *
	 * @since 1.0.0
	 * @param string $url Vimeo URL.
	 * @return string|false Video ID or false if not found.
	 */
	private function extract_vimeo_id( string $url ) {
		foreach ( $this->vimeo_patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}
		return false;
	}

	/**
	 * Parse Gutenberg blocks to extract video URLs
	 *
	 * Parses Gutenberg block comments to find embed blocks containing
	 * YouTube or Vimeo URLs.
	 *
	 * @since 1.0.0
	 * @param string $content Post content with Gutenberg blocks.
	 * @return array Array of detected videos with platform and ID.
	 */
	private function parse_gutenberg_blocks( string $content ): array {
		$videos = array();

		// Parse blocks using WordPress core function
		$blocks = parse_blocks( $content );

		// Recursively search for embed blocks
		$videos = $this->extract_videos_from_blocks( $blocks );

		return $videos;
	}

	/**
	 * Extract videos from parsed blocks recursively
	 *
	 * Recursively searches through blocks and inner blocks to find
	 * embed blocks containing video URLs.
	 *
	 * @since 1.0.0
	 * @param array $blocks Array of parsed blocks.
	 * @return array Array of detected videos with platform and ID.
	 */
	private function extract_videos_from_blocks( array $blocks ): array {
		$videos = array();

		foreach ( $blocks as $block ) {
			// Check if this is an embed block
			if ( 'core/embed' === $block['blockName'] ) {
				$url = $block['attrs']['url'] ?? '';

				if ( ! empty( $url ) ) {
					// Try to extract YouTube ID
					$youtube_id = $this->extract_youtube_id( $url );
					if ( false !== $youtube_id ) {
						$videos[] = array(
							'platform' => 'youtube',
							'id'       => $youtube_id,
						);
						continue;
					}

					// Try to extract Vimeo ID
					$vimeo_id = $this->extract_vimeo_id( $url );
					if ( false !== $vimeo_id ) {
						$videos[] = array(
							'platform' => 'vimeo',
							'id'       => $vimeo_id,
						);
					}
				}
			}

			// Check for YouTube-specific embed block
			if ( 'core-embed/youtube' === $block['blockName'] ) {
				$url = $block['attrs']['url'] ?? '';
				if ( ! empty( $url ) ) {
					$youtube_id = $this->extract_youtube_id( $url );
					if ( false !== $youtube_id ) {
						$videos[] = array(
							'platform' => 'youtube',
							'id'       => $youtube_id,
						);
					}
				}
			}

			// Check for Vimeo-specific embed block
			if ( 'core-embed/vimeo' === $block['blockName'] ) {
				$url = $block['attrs']['url'] ?? '';
				if ( ! empty( $url ) ) {
					$vimeo_id = $this->extract_vimeo_id( $url );
					if ( false !== $vimeo_id ) {
						$videos[] = array(
							'platform' => 'vimeo',
							'id'       => $vimeo_id,
						);
					}
				}
			}

			// Recursively check inner blocks
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_videos = $this->extract_videos_from_blocks( $block['innerBlocks'] );
				$videos = array_merge( $videos, $inner_videos );
			}
		}

		return $videos;
	}

	/**
	 * Parse classic editor content to extract oEmbed URLs
	 *
	 * Uses regex patterns to find YouTube and Vimeo URLs in classic editor content.
	 * Handles both plain URLs and URLs within HTML tags.
	 *
	 * @since 1.0.0
	 * @param string $content Classic editor post content.
	 * @return array Array of detected videos with platform and ID.
	 */
	private function parse_classic_editor_content( string $content ): array {
		$videos = array();

		// Detect YouTube videos
		$youtube_ids = $this->detect_youtube_videos( $content );
		foreach ( $youtube_ids as $id ) {
			$videos[] = array(
				'platform' => 'youtube',
				'id'       => $id,
			);
		}

		// Detect Vimeo videos
		$vimeo_ids = $this->detect_vimeo_videos( $content );
		foreach ( $vimeo_ids as $id ) {
			$videos[] = array(
				'platform' => 'vimeo',
				'id'       => $id,
			);
		}

		return $videos;
	}

	/**
	 * Fetch video metadata from platform API
	 *
	 * Attempts to fetch video metadata (title, description, thumbnail, duration)
	 * from the video platform's oEmbed API. Falls back gracefully on API failures.
	 *
	 * @since 1.0.0
	 * @param string $platform Video platform ('youtube' or 'vimeo').
	 * @param string $video_id Video ID.
	 * @return array|false Video metadata array or false on failure.
	 *                     Format: ['title' => '', 'description' => '', 'thumbnail_url' => '', 'duration' => '']
	 */
	public function fetch_video_metadata( string $platform, string $video_id ) {
		if ( 'youtube' === $platform ) {
			return $this->fetch_youtube_metadata( $video_id );
		} elseif ( 'vimeo' === $platform ) {
			return $this->fetch_vimeo_metadata( $video_id );
		}

		return false;
	}

	/**
	 * Fetch YouTube video metadata using oEmbed API
	 *
	 * Uses YouTube's oEmbed API to fetch video metadata without requiring an API key.
	 * Extracts title and thumbnail URL from the response.
	 *
	 * @since 1.0.0
	 * @param string $video_id YouTube video ID.
	 * @return array|false Video metadata array or false on failure.
	 */
	private function fetch_youtube_metadata( string $video_id ) {
		// Use YouTube oEmbed API (no API key required)
		$url = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . urlencode( $video_id ) . '&format=json';

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Handle API failures gracefully
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return false;
		}

		// Extract metadata from oEmbed response
		return array(
			'title'         => $data['title'] ?? '',
			'description'   => '', // Not available from oEmbed
			'thumbnail_url' => $data['thumbnail_url'] ?? '',
			'duration'      => '', // Not available from oEmbed
		);
	}

	/**
	 * Fetch Vimeo video metadata using oEmbed API
	 *
	 * Uses Vimeo's oEmbed API to fetch video metadata.
	 * Extracts title, description, thumbnail URL, and duration from the response.
	 *
	 * @since 1.0.0
	 * @param string $video_id Vimeo video ID.
	 * @return array|false Video metadata array or false on failure.
	 */
	private function fetch_vimeo_metadata( string $video_id ) {
		// Use Vimeo oEmbed API
		$url = 'https://vimeo.com/api/oembed.json?url=https://vimeo.com/' . urlencode( $video_id );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Handle API failures gracefully
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return false;
		}

		// Extract metadata from oEmbed response
		// Duration is in seconds, convert to ISO 8601 duration format (PT#S)
		$duration = '';
		if ( isset( $data['duration'] ) && is_numeric( $data['duration'] ) ) {
			$duration = 'PT' . (int) $data['duration'] . 'S';
		}

		return array(
			'title'         => $data['title'] ?? '',
			'description'   => $data['description'] ?? '',
			'thumbnail_url' => $data['thumbnail_url'] ?? '',
			'duration'      => $duration,
		);
	}
}
