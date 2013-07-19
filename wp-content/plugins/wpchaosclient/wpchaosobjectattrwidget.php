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
			'type' => 'select',
			'list' => array(),
			'val' => '',
		),
		array(
			'title' => 'Markup',
			'name' => 'markup',
			'type' => 'textarea',
			'val' => '%s',
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
		if(WPChaosClient::get_object()) {
			echo $args['before_widget'];
			printf($instance['markup'], WPChaosClient::get_object()->$instance['attribute']);

			//echo preg_replace_callback("/\[(\w+)\]/", function($matches) { WPChaosClient::get_object()->$matches[0]}, )

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

		// Populate list of attributes (added filters)
		
		$this->fields[0]['list'] = WPChaosClient::get_chaos_attributes();

		//Set title of widget
		$title = isset( $instance[ 'attribute' ]) ? ucfirst($instance['attribute']) : "";
		echo '<input type="hidden" id="'.$this->get_field_id('title').'" value="'.$title.'">';


		foreach($this->fields as $field) {
			$value = isset( $instance[ $field['name'] ]) ? $instance[ $field['name'] ] : $field['val'];
			$name = $this->get_field_name( $field['name'] );
			$title = $field['title'];
			$id = $this->get_field_id( $field['name'] );

			echo '<p>';
			echo '<label for="'.$name.'">'.$title.'</label>';
			switch($field['type']) {
				case 'textarea':
					echo '<textarea class="widefat" name="'.$name.'" >'.$value.'</textarea>';
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