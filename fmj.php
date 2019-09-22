<?php
/*
Plugin Name: Five Minute Journal
Plugin URI: http://fourhourworkweek.com/2015/01/15/morning-pages/
Description: Simple daily journal
Version: 0.1.0
Author: jackreichert
Author URI: http://www.jackreichert.com
Text Domain: five_min
*/


$Five_Minute_Journal = new Five_Minute_Journal();


class Five_Minute_Journal {
	private $questions;

	function __construct() {
		add_action( 'init', array( $this, 'five_minute_journal' ), 0 );
		add_action( 'add_meta_boxes', array( $this, 'five_minute_journal_register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'five_minute_journal_save_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'fmj_style' ) );
		add_filter( 'the_content', array( $this, 'fmj_content' ) );

		$this->questions = array(
			'morning' => array(
				'grateful'    => array(
					'question' => 'I am grateful for...',
					'count'    => 3
				),
				'great_today' => array(
					'question' => 'What would make today great?',
					'count'    => 3
				),
				'affirmation' => array(
					'question' => 'Daily affirmations. I am...',
					'count'    => 2
				),
			),
			'evening' => array(
				'amazing_things'    => array(
					'question' => 'Amazing things that happened today...',
					'count'    => 3
				),
				'made_today_better' => array(
					'question' => 'How could I have made today better?',
					'count'    => 2
				),
			)
		);
	}

	function fmj_style() {
		wp_enqueue_style( 'fmj_admin_style', plugin_dir_url( __FILE__ ) . '/fmj-admin.css' );
	}

	// Register Custom Post Type
	function five_minute_journal() {

		$labels = array(
			'name'                  => _x( 'Journal Entries', 'Post Type General Name', 'five_min' ),
			'singular_name'         => _x( 'Journal Entry', 'Post Type Singular Name', 'five_min' ),
			'menu_name'             => __( '5 Minute Journal', 'five_min' ),
			'name_admin_bar'        => __( '5 Minute Journal', 'five_min' ),
			'archives'              => __( 'Journal Archives', 'five_min' ),
			'parent_item_colon'     => __( 'Parent entry:', 'five_min' ),
			'all_items'             => __( 'All Entries', 'five_min' ),
			'add_new_item'          => __( 'Add Today\'s Entry', 'five_min' ),
			'add_new'               => __( 'Add New Entry', 'five_min' ),
			'new_item'              => __( 'New Entry', 'five_min' ),
			'edit_item'             => __( 'Edit Entry', 'five_min' ),
			'update_item'           => __( 'Update Entry', 'five_min' ),
			'view_item'             => __( 'View Entry', 'five_min' ),
			'search_items'          => __( 'Search Entry', 'five_min' ),
			'not_found'             => __( 'Not found', 'five_min' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'five_min' ),
			'featured_image'        => __( 'Featured Image', 'five_min' ),
			'set_featured_image'    => __( 'Set featured image', 'five_min' ),
			'remove_featured_image' => __( 'Remove featured image', 'five_min' ),
			'use_featured_image'    => __( 'Use as featured image', 'five_min' ),
			'insert_into_item'      => __( 'Insert into item', 'five_min' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'five_min' ),
			'items_list'            => __( 'Items list', 'five_min' ),
			'items_list_navigation' => __( 'Items list navigation', 'five_min' ),
			'filter_items_list'     => __( 'Filter items list', 'five_min' ),
		);
		$args   = array(
			'label'               => __( 'Journal Entry', 'five_min' ),
			'description'         => __( 'Five Minute Journal', 'five_min' ),
			'labels'              => $labels,
			'supports'            => array( 'revisions' ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-book-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'journal', $args );

	}

	/**
	 * Register meta box(es).
	 */
	function five_minute_journal_register_meta_boxes() {
		add_meta_box( 'today', __( 'Today is:', 'five_min' ), array( $this, 'today_display_callback' ), 'journal', 'normal', 'high' );
		add_meta_box( 'fmj_morning', __( 'Morning', 'five_min' ), array( $this, 'morning_display_callback' ), 'journal', 'normal', 'high' );
	}


	/**
	 * Meta box display callback.
	 *
	 * @param WP_Post $post Current post object.
	 */
	function today_display_callback( $post ) {
		wp_nonce_field( 'five_min', 'five_min_nonce' );
		$d     = new DateTime( $post->post_date );
		$title = ( '' == $post->post_title ) ? $d->format( 'l F j, Y' ) : $post->post_title; ?>
		<h3><?php echo $title; ?> - <small>(<a href="<?php echo get_the_permalink($post->ID); ?>">view entry</a>)</small></h3>
		<input type="hidden" name="post_title" value="<?php echo $title; ?>">
		<?php
	}

	/**
	 * Meta box display callback.
	 *
	 * @param WP_Post $post Current post object.
	 */
	function morning_display_callback( $post ) {
		$five_min = maybe_unserialize( get_post_meta( $post->ID, 'five_min', true ) ); ?>
		<?php foreach ( $this->questions as $tod => $questions ) : ?>
			<article>
				<h1><?php echo ucwords( $tod ); ?></h1>
				<?php foreach ( $questions as $label => $question ) : ?>
					<h3><?php echo $this->questions[$tod][$label]['question']; ?></h3>
					<ol>
						<?php for ( $i = 0; $i < $this->questions[$tod][$label]['count']; $i ++ ) : ?>
							<li>
								<input type="text" class="entry_line" name="five_min[<?php echo $tod; ?>][<?php echo $label; ?>][]" value="<?php echo $five_min[$tod][$label][$i]; ?>" />
							</li>
						<?php endfor; ?>
					</ol>
				<?php endforeach; ?>
			</article>
			<hr>
		<?php endforeach;
	}

	/**
	 * Save meta box content.
	 *
	 * @param int $post_id Post ID
	 */
	function five_minute_journal_save_meta_box( $post_id ) {
		if ( ! isset( $_POST['five_min_nonce'] ) ) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['five_min_nonce'], 'five_min' ) ) {
			return $post_id;
		}

		if ( 'journal' == get_post_type( $post_id ) ) {
			update_post_meta( $post_id, 'five_min', maybe_serialize( $_POST['five_min'] ) );

			// make these private
			remove_action( 'save_post', array( $this, 'five_minute_journal_save_meta_box' ) );
			// update the post, which calls save_post again
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'private' ) );
			// re-hook this function
			add_action( 'save_post', array( $this, 'five_minute_journal_save_meta_box' ) );
		}

	}

	function fmj_content( $content ) {
		global $post;
		if ( 'journal' == $post->post_type ) {
			$five_min = maybe_unserialize( get_post_meta( $post->ID, 'five_min', true ) );
			foreach ( $this->questions as $tod => $questions ) {
				$content .= '<article>';
				$content .= '<h1>' . ucwords( $tod ) . '</h1>';
				foreach ( $questions as $label => $question ) {
					$content .= '<h3>' . $this->questions[$tod][$label]['question'] . '</h3>';
					$content .= '<ol>';
					for ( $i = 0; $i < $this->questions[$tod][$label]['count']; $i ++ ) {
						$content .= '<li>' . $five_min[$tod][$label][$i] . '</li>';
					}
					$content .= '</ol>';
				}
				$content .= '</article>';
				$content .= '<hr>';
			}
		}
		return $content;
	}
}
