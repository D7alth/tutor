<?php
namespace TUTOR;

if ( ! defined( 'ABSPATH' ) )
	exit;

if (! class_exists('Tutor_List_Table')){
	include_once tutor()->path.'classes/Tutor_List_Table.php';
}

use TUTOR\Backend_Page_Trait;

class Instructors_List extends \Tutor_List_Table {

	const INSTRUCTOR_LIST_PAGE = 'tutor-instructors';
	/*
	function __construct(){
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'instructor',     //singular name of the listed records
			'plural'    => 'instructors',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );

		//$this->process_bulk_action();
	}*/



	/**
	 * Trait for utilities
	 *
	 * @var $page_title
	 */

	use Backend_Page_Trait;
	/**
	 * Page Title
	 *
	 * @var $page_title
	 */
	public $page_title;

	/**
	 * Bulk Action
	 *
	 * @var $bulk_action
	 */
	public $bulk_action = true;

	/**
	 * Handle dependencies
	 */
	public function __construct() {
		$this->page_title = __( 'Instructor', 'tutor' );
		/**
		 * Handle bulk action
		 *
		 * @since v2.0.0
		 */
		add_action( 'wp_ajax_tutor_instructor_bulk_action', array( $this, 'instructor_bulk_action' ) );
	}

	/**
	 * Available tabs that will visible on the right side of page navbar
	 *
	 * @param string $user_id selected instructor id | optional.
	 * @param string $date selected date | optional.
	 * @param string $search search by user name or email | optional.
	 * @return array
	 * @since v2.0.0
	 */
	public function tabs_key_value( $instructor_id, $date, $search ): array {
		$url       = get_pagenum_link();
		$approve  = self::get_instructor_number( 'approved', $instructor_id, $date, $search );
		$pending = self::get_instructor_number( 'pending', $instructor_id, $date, $search );
		$blocked = self::get_instructor_number( 'blocked', $instructor_id, $date, $search );
		$tabs      = array(
			array(
				'key'   => 'all',
				'title' => __( 'All', 'tutor-pro' ),
				'value' => $approve + $pending + $blocked,
				'url'   => $url . '&data=all',
			),
			array(
				'key'   => 'approved',
				'title' => __( 'Approve', 'tutor-pro' ),
				'value' => $approve,
				'url'   => $url . '&data=approved',
			),
			array(
				'key'   => 'pending',
				'title' => __( 'Pending', 'tutor-pro' ),
				'value' => $pending,
				'url'   => $url . '&data=pending',
			),
			array(
				'key'   => 'blocked',
				'title' => __( 'Block', 'tutor-pro' ),
				'value' => $blocked,
				'url'   => $url . '&data=blocked',
			),
		);
		return $tabs;
	}

	/**
	 * Prepare bulk actions that will show on dropdown options
	 *
	 * @return array
	 * @since v2.0.0
	 */
	public function prpare_bulk_actions(): array {
		$actions = array(
			$this->bulk_action_default(),
			$this->bulk_action_delete(),
		);
		return $actions;
	}

	/**
	 * Count enrolled number by status & filters
	 * Count all enrollment | approved | cancelled
	 *
	 * @param string $status | required.
	 * @param string $user_id selected user id | optional.
	 * @param string $date selected date | optional.
	 * @param string $search_term search by user name or email | optional.
	 * @return int
	 * @since v2.0.0
	 */
	protected static function get_instructor_number( $status = '', $user_id = '', $date = '', $search_term = ''  ): int {
		global $wpdb;
		$status      = sanitize_text_field( $status );
		$user_id   = sanitize_text_field( $user_id );
		$date        = sanitize_text_field( $date );
		$search_term = sanitize_text_field( $search_term );

		$search_term = '%' . $wpdb->esc_like( $search_term ) . '%';

		// add course id in where clause.
		$instructor_query = '';
		if ( '' !== $user_id ) {
			$instructor_query = "AND instructor.ID = $user_id";
		}

		// add date in where clause.
		$date_query = '';
		if ( '' !== $date ) {
			$date_query = "AND DATE(user.user_registered) = CAST('$date' AS DATE) ";
		}

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user.*, user_meta.meta_value AS instructor_from_date
					FROM 	{$wpdb->users} user
					INNER JOIN {$wpdb->usermeta} user_meta
							ON ( user.ID = user_meta.user_id )
					INNER JOIN {$wpdb->usermeta} inst_status
							ON ( user.ID = inst_status.user_id )
							WHERE 	user_meta.meta_key = %s
					AND ( user.display_name LIKE %s OR user.user_email LIKE %s )
					",
				'_is_tutor_instructor',
				$status,
				$search_term,
				$search_term,
				$search_term,
				$search_term
			)
		);
		return $count ? $count : 0;
	}

	/**
	 * Handle bulk action for instructor delete
	 *
	 * @return string JSON response.
	 * @since v2.0.0
	 */
	public function instructor_bulk_action() {
		// check nonce.
		tutor_utils()->checking_nonce();
		$status   = isset( $_POST['bulk-action'] ) ? sanitize_text_field( $_POST['bulk-action'] ) : '';
		$bulk_ids = isset( $_POST['bulk-ids'] ) ? sanitize_text_field( $_POST['bulk-ids'] ) : array();
		$update   = self::update_instructors( $status, $bulk_ids );
		return true === $update ? wp_send_json_success() : wp_send_json_error();
		exit;
	}

	/**
	 * Execute bulk action for enrollments ex: complete | cancel
	 *
	 * @param string $status hold status for updating.
	 * @param string $enrollment_ids ids that need to update.
	 * @return bool
	 * @since v2.0.0
	 */
	public static function update_instructors( $status, $enrollment_ids ): bool {
		global $wpdb;
		$instructor_table = $wpdb->user_meta;
		$update     = $wpdb->query(
			$wpdb->prepare(
				" UPDATE {$instructor_table}
				SET inst_status = %s 
				WHERE ID IN ($users_ids)
			",
				$status
			)
		);
		return false === $update ? false : true;
	}

	function column_default($item, $column_name){
		switch($column_name){
			case 'user_email':
			case 'display_name':
				return $item->$column_name;
			case 'registration_date':
				return esc_html( date( get_option( 'date_format'), strtotime($item->user_registered ) ) );
			default:
				return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
	}

	function column_total_course($item){
		global $wpdb;
		$course_post_type = tutor()->course_post_type;

		$total_course = (int) $wpdb->get_var($wpdb->prepare("SELECT count(ID) from {$wpdb->posts} WHERE post_author=%d AND post_type=%s ", $item->ID, $course_post_type));

		echo $total_course;
	}

	/**
	 * @param $item
	 *
	 * Completed Course by User
	 */
	function column_status($item){
		$status = tutor_utils()->instructor_status($item->ID, false);
		$status_name = tutor_utils()->instructor_status($item->ID);
		echo "<span class='tutor-status-context tutor-status-{$status}-context'>{$status_name}</span>";
	}

	function column_display_name($item) {
		//Build row actions
		$actions = array();

		$status = tutor_utils()->instructor_status($item->ID, false);

		switch ($status){
			case 'pending':
				$actions['approved'] = sprintf('<a class="instructor-action" data-action="approve" data-instructor-id="'.$item->ID.'" href="?page=%s&action=%s&instructor=%s">'.__('Approve', 'tutor').'</a>', self::INSTRUCTOR_LIST_PAGE, 'approve', $item->ID);
				break;
			case 'approved':
				$actions['blocked'] = sprintf('<a data-prompt-message="'.__('Sure to Block?', 'tutor').'" class="instructor-action" data-action="blocked" data-instructor-id="'.$item->ID.'" href="?page=%s&action=%s&instructor=%s">'.__('Block', 'tutor').'</a>', self::INSTRUCTOR_LIST_PAGE, 'blocked', $item->ID);
				break;
			case 'blocked':
				$actions['approved'] = sprintf('<a data-prompt-message="'.__('Sure to Un Block?', 'tutor').'" class="instructor-action" data-action="approve" data-instructor-id="'.$item->ID.'" href="?page=%s&action=%s&instructor=%s">'.__('Unblock', 'tutor').'</a>', self::INSTRUCTOR_LIST_PAGE, 'approve', $item->ID);
				break;
		}

		// Add user edit link
		$edit_link = get_edit_user_link($item->ID);
		$edit_link = '<a href="'.$edit_link.'">'.__('Edit').'</a>';
		$actions['tutor-instructor-edit-link']=$edit_link;

		// Add remove instructor action
		$removal_title = $status=='pending' ? __('Reject', 'tutor') : __('Remove as Instructor', 'tutor');
		$removal_warning = $status=='pending' ? __('Sure to Reject?', 'tutor') : __('Sure to Remove as Instructor?', 'tutor');
		$actions['tutor-remove-instructor'] = sprintf('<a data-prompt-message="' . $removal_warning . '" class="instructor-action" data-action="remove-instructor" data-instructor-id="'.$item->ID.'"  href="?page=%s&action=%s&instructor=%s">' . $removal_title . '</a>', self::INSTRUCTOR_LIST_PAGE, 'remove-instructor', $item->ID);

		//Return the title contents
		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			$item->display_name,
			$item->ID,
			$this->row_actions($actions)
		);
	}

	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("instructor")
			/*$2%s*/ $item->ID                //The value of the checkbox should be the record's id
		);
	}

	function column_instructor_commission($item){
		$commision = apply_filters('tutor_pro_instructor_commission_string', $item->ID);

		// If the return value is numeric, it means the filter was not executed.
		// may be pro is not installed. So show N\A. The return value will something like '23 percent'

		return !is_numeric($commision) ? $commision : 'N\\A';
	}

	function get_columns(){
		$columns = array(
			'cb'                		=> '<input type="checkbox" />', //Render a checkbox instead of text
			'display_name'      		=> __('Name', 'tutor'),
			'user_email'        		=> __('E-Mail', 'tutor'),
			'total_course'      		=> __('Total Course', 'tutor'),
			'instructor_commission' 	=> __('Instructor Commission', 'tutor'),
			'registration_date' 		=> __('Date', 'tutor'),
			'status'            		=> __('Status', 'tutor'),
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			//'display_name'     => array('title',false),     //true means it's already sorted
		);
		return $sortable_columns;
	}


	function process_bulk_action() {
		if( 'approve' === $this->current_action() ) {
			$instructor_id = (int) sanitize_text_field($_GET['instructor']);

			do_action('tutor_before_approved_instructor', $instructor_id);

			update_user_meta($instructor_id, '_tutor_instructor_status', 'approved');
			update_user_meta($instructor_id, '_tutor_instructor_approved', tutor_time());

			$instructor = new \WP_User($instructor_id);
			$instructor->add_role(tutor()->instructor_role);

			//TODO: send E-Mail to this user about instructor approval, should via hook
			do_action('tutor_after_approved_instructor', $instructor_id);
		}

		if( 'blocked' === $this->current_action() ) {
			$instructor_id = (int) sanitize_text_field($_GET['instructor']);

			do_action('tutor_before_blocked_instructor', $instructor_id);
			update_user_meta($instructor_id, '_tutor_instructor_status', 'blocked');

			$instructor = new \WP_User($instructor_id);
			$instructor->remove_role(tutor()->instructor_role);
			do_action('tutor_after_blocked_instructor', $instructor_id);

			//TODO: send E-Mail to this user about instructor blocked, should via hook
		}

		//Detect when a bulk action is being triggered...
		if( 'delete'=== $this->current_action() ) {
			
			$delete_instructors = $_GET['instructor'];
			if ( count($delete_instructors) ) {
				foreach( $delete_instructors as $instructor ) {
					do_action( 'tutor_insctructor_before_delete', $instructor);

					wp_delete_user($instructor);

					do_action( 'tutor_insctructor_after_delete', $instructor);

				}
			}
		}
	}

	/**
	 * Filter support added
	 * 
	 * @param optional
	 * 
	 * @since 1.9.7
	 */
	function prepare_items($search_filter = '', $course_filter = '', $date_filter = '', $order_filter = '') {
		$per_page = 20;

		// $search_term = '';
		// if (isset($_REQUEST['s'])){
		// 	$search_term = sanitize_text_field($_REQUEST['s']);
		// }

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();
		$current_page = $this->get_pagenum();

		$total_items = tutor_utils()->get_total_instructors($search_filter);
		$this->items = tutor_utils()->get_instructors(($current_page-1)*$per_page, $per_page, $search_filter, $course_filter, $date_filter,$order_filter, $status = null, $cat_ids = array() );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );
	}
}