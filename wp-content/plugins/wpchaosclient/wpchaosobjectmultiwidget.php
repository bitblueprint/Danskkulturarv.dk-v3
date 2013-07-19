<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */

class WPChaosObjectMultiWidget extends WP_Widget {

	private $fields = array(
		array(
			'title' => 'Markup',
			'name' => 'markup',
			'type' => 'textarea',
			'val' => '',
		)
	);

	public function __construct() {
		
		parent::__construct(
			'chaos-object-multi-widget',
			'CHAOS Object Multi Attributes',
			array( 'description' => 'Style and display several data from a CHAOS object' )
		);

	}

	public function widget( $args, $instance ) {
		if(WPChaosClient::get_object()) {
			echo $args['before_widget'];
			
			echo preg_replace_callback("/\[(\w+)\]/", 
				function($matches) {

					return WPChaosClient::get_object()->$matches[1];

				}, $instance['markup']);

			echo $args['after_widget'];
		}
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
			$value = isset( $instance[ $field['name'] ]) ? $instance[ $field['name'] ] : $field['val'];
			$name = $this->get_field_name( $field['name'] );
			$title = $field['title'];
			$id = $this->get_field_id( $field['name'] );

			echo '<p>';
			echo '<label for="'.$name.'">'.$title.'</label>';
			switch($field['type']) {
				case 'textarea':
					echo '<textarea class="widefat" rows="16" cols="20" name="'.$name.'" >'.$value.'</textarea>';
					break;
				case 'select':
					echo '<select class="widefat" name="'.$name.'">';
					foreach($field['list'] as $opt_key => $opt_value) {
						echo '<option value="'.$opt_key.'" '.selected( $value, $opt_key, false).'>'.$opt_value.'</option>';
					}
					echo '</select>';
					break;
				case 'text':
				default:
					echo '<input class="widefat" id="'.$id.'" name="'.$name.'" type="text" value="'.esc_attr( $value ).'" />';
			}
			echo '</p>';
		}
		echo '<p>Allowed attributes:<br>';
		if(count(WPChaosClient::get_chaos_attributes()) > 0) {
			echo '['.implode('], [',array_keys(WPChaosClient::get_chaos_attributes())).']</p>';
		} else {
			echo 'None';
		}
		
	}

	public function update( $new_instance, $old_instance ) {

		$instance = array();
		
		foreach($this->fields as $field) {
			$instance[$field['name']] = ( ! empty( $new_instance[$field['name']] ) ) ? $new_instance[$field['name']]  : $field['val'];
		}
		
		return $instance;
	}

}

//eol