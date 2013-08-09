<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */

/**
 * WordPress Widget that display a search form
 * to be used with CHAOS
 */
class WPChaos_Search_Widget extends WP_Widget {

	/**
	 * Fields in widget. Defines keys for values
	 * @var array
	 */
	private $fields;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'chaos-search',
			__('CHAOS Search','wpchaossearch'),
			array( 'description' => __('Adds form to search in CHAOS material','wpchaossearch') )
		);

		$this->fields = array(
			array(
				'title' => __('Title','wpchaossearch'),
				'name' => 'title'
			),
			array(
				'title' => __('Placeholder','wpchaossearch'),
				'name' => 'placeholder'
			)
		);
	}

	/**
	 * GUI for widget content
	 * 
	 * @param  array $args Sidebar arguments
	 * @param  array $instance Widget values from database
	 * @return void 
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		
		WPChaosSearch::create_search_form($instance['placeholder']);
		
		echo $args['after_widget'];
	}

	/**
	 * GUI for widget form in the administration
	 * 
	 * @param  array $instance Widget values from database
	 * @return void           
	 */
	public function form( $instance ) {
		
		foreach($this->fields as $field) {
			$title = isset( $instance[ $field['name'] ]) ? $instance[ $field['name'] ] : "";
			echo '<p>';
			echo '<label for="'.$this->get_field_name( $field['name'] ).'">'.$field['title'].'</label>';
			echo '<input class="widefat" id="'.$this->get_field_id( $field['name'] ).'" name="'.$this->get_field_name( $field['name'] ).'" type="text" value="'.esc_attr( $title ).'" />';
			echo '</p>';
		}
	}

	/**
	 * Callback for whenever the widget values should be saved
	 * 
	 * @param  array $new_instance New values from the form
	 * @param  array $old_instance Previously saved values
	 * @return array               Values to be saved
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		foreach($this->fields as $field) {
			$instance[$field['name']] = ( ! empty( $new_instance[$field['name']] ) ) ? strip_tags( $new_instance[$field['name']] ) : '';
		}

		return $instance;
	}

}

//eol