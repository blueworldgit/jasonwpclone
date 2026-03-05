<?php

    add_action('widgets_init', 'register_user_vehicle_filter_widget');
    function register_user_vehicle_filter_widget(){
    	register_widget( 'Enovathemes_Addons_WP_User_Vehicle_Filter' );
    }

    class Enovathemes_Addons_WP_User_Vehicle_Filter extends WP_Widget {

    	public function __construct() {
    		parent::__construct(
    			'user_vehicle_filter_widget',
    			esc_html__('* User vehicle filter', 'enovathemes-addons'),
    			array( 'description' => esc_html__('User vehicle filter', 'enovathemes-addons'))
    		);
    	}

    	public function widget( $args, $instance) { global $wpdb;

    		extract($args);

    		$title              = isset($instance['title']) ? apply_filters( 'widget_title', $instance['title'] ) : '';
            $atts               = isset($instance['atts']) ? esc_attr($instance['atts']) : '';
            $columns            = isset($instance['columns']) ? esc_attr($instance['columns']) : 1;
            $vin                = isset($instance['vin']) ? esc_attr($instance['vin']) : 'off';
            $vin_decoder        = (get_theme_mod('vin_decoder') != null && !empty(get_theme_mod('vin_decoder'))) ? get_theme_mod('vin_decoder') : false;

    		echo $before_widget;

            if (!empty($atts)) {

                $atts = json_decode( html_entity_decode( stripslashes ($atts)),true );

                $atts_count = (is_array($atts)) ? esc_attr(count($atts)) : 0;
                $class = array();

                if ($vin == "on") {
                    $class[] = 'vin';
                }

                $element_id = rand(1,1000000);

                $class[] = 'vertical';
                $class[] = 'user-vehicle-filter-'.$element_id;

                $current_vehicle = '';

                if (isset($_GET['vin']) && !empty($_GET['vin'])) {
                    $vehicle_attributes = enovathemes_addons_vin_decoder($_GET['vin']);
                    $vehicle_data       = enovathemes_addons_vin_decoder($_GET['vin'],true);

                    if ($vehicle_attributes) {
                        $vehicle = $vehicle_attributes;
                    }

                   
                    if ($vehicle_attributes) {
                        $current_vehicle = 'data-vehicle="'.htmlspecialchars(json_encode($vehicle_attributes)).'"';
                    }

                }

                ?>

                <a href="#" class="add-more hidden"><?php echo esc_html__('Add more','enovathemes-addons'); ?></a>

                <form name="user-vehicle-filter" class="user-vehicle-filter vehicle-filter <?php echo implode(' ', $class); ?>" data-rem="<?php echo (($atts_count > $columns && $atts_count % $columns == 0) ? 'true' : 'false'); ?>" data-count="<?php echo $columns; ?>" <?php echo $current_vehicle; ?> method="POST">
                    <div class="atts">
                        <?php

                            $i = 0;

                            foreach ($atts as $att) {
                                $first = ($i == 0) ? true : false;
                                enovathemes_addons_render_vehicle_filter_attribute($att,$first,false);
                                $i++;
                            }

                        ?>

                        
                    </div>
                    <div class="last">

                        <?php if ($vin == "on"): ?>
                            <div class="vin">
                                
                                <?php

                                    switch ($vin_decoder) {
                                        case 'https://app.auto-ways.net/api/v1':
                                            $vin_label = esc_html__('Search by PLATE','enovathemes-addons');
                                            break;
                                        case 'https://api.vehicledatabases.com/uk-registration-decode':
                                        case 'https://uk1.ukvehicledata.co.uk/api/datapackage/VehicleData':
                                        case 'https://api.biluppgifter.se/api/v1/vehicle/regno':
                                            $vin_label = esc_html__('Search by REG NUMBER','enovathemes-addons');
                                            break;
                                        default:
                                            $vin_label = esc_html__('Search by VIN','enovathemes-addons');
                                            break;
                                    }

                                ?>

                                <span><?php echo esc_html__('OR','enovathemes-addons'); ?></span>
                                <input type="text" class="vin" value="" placeholder="<?php echo esc_attr($vin_label); ?>">
                            </div>
                        <?php endif ?>
                        
                        <input type="hidden" id="nonce" name="nonce" value="<?php echo esc_attr(wp_create_nonce('user-vehicle-filter')); ?>">
                        <input type="submit" value="<?php echo esc_html__('Add vehicle','enovathemes-addons'); ?>">
                    </div>
                    <span class="reset"><?php echo esc_html__('Reset','enovathemes-addons'); ?></span>
                </form>

                <ul class="user-vehicle-list hidden"> 
                </ul>

                <div class="et-clearfix"></div>

    		<?php } echo $after_widget;
    	}

     	public function form( $instance ) {

     		$defaults = array(
                'title' => esc_html__('User vehicle filter', 'enovathemes-addons'),
                'atts'  => '',
                'vin'   => 'off',
                'columns'   => 1,
     		);

     		$instance = wp_parse_args((array) $instance, $defaults);

            $categories = get_product_categories_hierarchy(false);

    		?>

    		<div id="<?php echo esc_attr($this->get_field_id( 'widget_id' )); ?>" class="widget-user-vehicle-filter">

                <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo esc_html__( 'Title:', 'enovathemes-addons' ); ?></label>
                    <input class="widefat <?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
                </p>

                <p>
                    <label for="<?php echo $this->get_field_id( 'vin' ); ?>">
                        <input type="checkbox" class="widefat <?php echo $this->get_field_id( 'vin' ); ?>" name="<?php echo $this->get_field_name( 'vin' ); ?>" <?php checked($instance['vin'], 'on'); ?> value="on" />
                        <?php echo esc_html__( 'Enable vin search', 'enovathemes-addons' ); ?>
                    </label>
                </p>

                <p>
                    <label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php echo esc_html__( 'Columns:', 'enovathemes-addons' ); ?></label>
                    <select class="widefat column" id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" >
                        <?php for ($i=1; $i < 3; $i++) { ?>
                            <option class="col<?php echo $i; ?>" value="<?php echo $i; ?>" <?php selected( $instance['columns'], $i ); ?>><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </p>

                <div class="sortable-droppable-attributes">

                    <?php

                    $vehicle_params = apply_filters( 'vehicle_params','');

                    $options = '<span class="remove" title="'.esc_html__( 'Remove', 'enovathemes-addons' ).'"></span>';

                    ?>

                    <?php if ($vehicle_params): ?>
                        <h4><?php echo esc_html__( 'Available filter attributes', 'enovathemes-addons' ); ?></h4>
                        <ul class="draggable">
                            <?php if ($vehicle_params): ?>
                                <?php foreach ($vehicle_params as $param) { ?>
                                    <li data-attribute='{"attr":"<?php echo esc_attr($param); ?>","label":"<?php echo esc_attr(ucfirst($param)); ?>"}' data-title="<?php echo esc_attr(ucfirst($param)); ?>" class="draggable-item vehicle">
                                        <span class="remove" title="<?php echo esc_html__( 'Remove', 'enovathemes-addons' ); ?>"></span>
                                        <?php echo esc_html(ucfirst($param)); ?>
                                        <input type="text" name="label" placeholder="<?php echo esc_html__('Label','enovathemes-addons') ?>">
                                    </li>
                                <?php } ?>
                            <?php endif ?>
                        </ul>
                        <h4><?php echo esc_html__( 'Drop here filter attributes', 'enovathemes-addons' ); ?></h4>
                        <ul class="sortable"></ul>
                    <?php endif ?>

                    <input class="atts" type="hidden" id="<?php echo $this->get_field_id('atts'); ?>" name="<?php echo $this->get_field_name('atts'); ?>" value="<?php echo esc_attr( $instance['atts'] ); ?>" />

                </div>

    		</div>

    		<?php
    	}

    	public function update( $new_instance, $old_instance ) {
    		$instance = $old_instance;
            $instance['title'] = strip_tags( $new_instance['title'] );
            $instance['atts']  = strip_tags( $new_instance['atts'] );
            $instance['columns']  = strip_tags( $new_instance['columns'] );
            $instance['vin']   = (isset($new_instance['vin'])) ? strip_tags( $new_instance['vin'] ) : 'off';
    		return $instance;
    	}

    }

?>