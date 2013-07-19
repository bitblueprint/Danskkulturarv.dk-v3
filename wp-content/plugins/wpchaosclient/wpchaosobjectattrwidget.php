<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */

class WPChaosObjectAttrWidget extends WP_Widget {

	private $fields = array(
		array(
			'title' => 'Attribute',
			'name' => 'attribute',
			'type' => 'text',
		),
		array(
			'title' => 'Markup',
			'name' => 'markup',
			'type' => 'text',
		)
	);

	public function __construct() {
		parent::__construct(
			'chaos-object-attribute-widget',
			'CHAOS Object Attribute',
			array( 'description' => 'Style and display data from a CHAOS object' )
		);
	}

	public function widget( $args, $instance ) {

		echo $args['before_widget'];
		
		printf($instance['markup'], WPChaosClient::get_object()->$instance['attribute']);
		
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

			switch($field['type']) {
				case 'text':
				default:
					echo '<p>';
					echo '<label for="'.$this->get_field_name( $field['name'] ).'">'.$field['title'].'</label>';
					echo '<input class="widefat" id="'.$this->get_field_id( $field['name'] ).'" name="'.$this->get_field_name( $field['name'] ).'" type="text" value="'.esc_attr( $title ).'" />';
					echo '</p>';
			}	
		}
	}

	public function update( $new_instance, $old_instance ) {

		$instance = array();
		foreach($this->fields as $field) {
			$instance[$field['name']] = ( ! empty( $new_instance[$field['name']] ) ) ? $new_instance[$field['name']]  : '';
		}

		return $instance;
	}

}

//eol