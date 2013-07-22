<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */

/**
 * WordPress Widget that makes it possible to style
 * and display several data from a CHAOS object
 */
class WPChaosObjectMultiWidget extends WP_Widget {

	/**
	 * Fields in widget. Defines keys for values
	 * @var array
	 */
	private $fields = array(
		array(
			'title' => 'Markup',
			'name' => 'markup',
			'type' => 'textarea',
			'val' => '',
		)
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		
		parent::__construct(
			'chaos-object-multi-widget',
			'CHAOS Object Multi Attributes',
			array( 'description' => 'Style and display several data from a CHAOS object' )
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
		if(WPChaosClient::get_object()) {
			echo $args['before_widget'];
			
			//Find all occurences of [foo] in the markup and replace them
			//with a foo method on the current CHAOS object.
			echo preg_replace_callback("/\[(\w+)\]/", 
				function($matches) {

					return WPChaosClient::get_object()->$matches[1];

				}, $instance['markup']);

			echo $args['after_widget'];
		}
	}

	/**
	 * GUI for widget form in the administration
	 * 
	 * @param  array $instance Widget values from database
	 * @return void           
	 */
	public function form( $instance ) {

		//Print each field based on its type
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
		//List the attribute methods defined by WPChaosClient and wrap them with [].
		if(count(WPChaosClient::get_chaos_attributes()) > 0) {
			echo '['.implode('], [',array_keys(WPChaosClient::get_chaos_attributes())).']</p>';
		} else {
			echo 'None';
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
			$instance[$field['name']] = ( ! empty( $new_instance[$field['name']] ) ) ? $new_instance[$field['name']]  : $field['val'];
		}
		
		return $instance;
	}

}

//eol