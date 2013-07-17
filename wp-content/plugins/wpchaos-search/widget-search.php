<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */

class WPChaos_Search_Widget extends WP_Widget {

	private $fields = array(
		array(
			'title' => 'Title',
			'name' => 'title'
		),
		array(
			'title' => 'Placeholder',
			'name' => 'placeholder'
		)
	);

	public function __construct() {
		parent::__construct(
			'chaos-search',
			'CHAOS Search',
			array( 'description' => 'Adds fields to search in CHAOS material' )
		);
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		
		WPChaosSearch::create_search_form($instance['placeholder']);
		
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
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

	public function update( $new_instance, $old_instance ) {

		$instance = array();
		foreach($this->fields as $field) {
			$instance[$field['name']] = ( ! empty( $new_instance[$field['name']] ) ) ? strip_tags( $new_instance[$field['name']] ) : '';
		}

		return $instance;
	}

}

//eol