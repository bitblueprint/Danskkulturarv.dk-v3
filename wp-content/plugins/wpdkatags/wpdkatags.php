<?php
/*
Plugin Name: WP DKA Tags
Plugin URI: 
Description: Manage user generated tags for CHAOS
Version: 1.0
Author: Joachim Jensen
Author URI: 
License: 
*/

final class WPDKATags {

    const DOMAIN = 'wpdkatags';

    private static $plugin_dependencies = array(
        'wpchaosclient/wpchaosclient.php' => 'WordPress Chaos Client'
        );

    public function __construct() {
        if(self::check_chaosclient()) {
            if(is_admin()) {
                $this->load_dependencies();

                add_action('admin_menu', array(&$this,'add_menu_items'));
                add_filter('wpchaos-config',array(&$this,'add_chaos_settings'));
                add_action('wp_ajax_wpdkatags_submit_tag', array(&$this,'ajax_submit_tag') );
                add_action('wp_ajax_nopriv_wpdkatags_submit_tag', array(&$this,'ajax_submit_tag') );
            }
        

            add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'usertags', array(&$this,'define_usertags_filter'),10,2);
        }
    }

    public function add_chaos_settings($settings) {
        $new_settings = array(
            array(
                /*Sections*/
                'name'      => 'wpdkatags',
                'title'     => __('User Tags',self::DOMAIN),
                'fields'    => array(
                    /*Section fields*/
                    array(
                        'name' => 'wpdkatags-activate',
                        'title' => __('Allow tagging',self::DOMAIN),
                        'type' => 'select',
                        'list' => array(
                            'No',
                            'Yes'
                            )
                        )
                    )
                )
            );
        return array_merge($settings,$new_settings);
    }

    public function add_menu_items(){
        add_menu_page(
            'WP DKA Tags',
            'User Tags',
            'activate_plugins',
            'wpdkatags',
            array(&$this,'render_tags_page')
            );
    }

    function render_tags_page(){

        //Create an instance of our package class...
        $testListTable = new WPDKATags_List_Table();
        //Fetch, prepare, sort, and filter our data...
        $testListTable->prepare_items();
        
        ?>
        <div class="wrap">

            <div id="icon-users" class="icon32"><br/></div>
            <h2>User Tags</h2>

            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="movies-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <?php $testListTable->views(); ?>
                <!-- Now we can render the completed list table -->
                <?php $testListTable->display(); ?>
            </form>

        </div>
        <?php
    }

    public function ajax_submit_tag() {
        // Handle request then generate response using WP_Ajax_Response
        
        $tag = esc_html($_POST['tag']);

        if(!isset($_POST['object_guid'])) {
            throw new \RuntimeException("GUID not found");
        }
        $response = WPChaosClient::instance()->Object()->Get(
            WPChaosClient::escapeSolrValue($_POST['object_guid']),   // Search query
            null,   // Sort
            null,   // AccessPoint given by settings.
            0,      // pageIndex
            1,      // pageSize
            true,   // includeMetadata
            true,   // includeFiles
            true    // includeObjectRelations
        );

        $objects = WPChaosObject::parseResponse($response);
        if(empty($objects)) {
            throw new \RuntimeException("No object found for GUID");
        }

        \CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dkac', 'http://www.danskkulturarv.dk/DKA.Crowd.xsd');
        $xml = $objects[0]->get_metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID);
        
        $tags = $xml->xpath('/dkac:DKACrowd/dkac:Tags')[0];

        //$response = $xml->asXML();
        $tagnode = $tags->addChild('Tag',$tag);
        //$tagnode->addAttribute('created', date('c', time()));
        //$tagnode->addAttribute('status', 'Unapproved');
        //$response = $xml->asXML();

        if($objects[0]->set_metadata(WPChaosClient::instance(),WPDKAObject::DKA_CROWD_SCHEMA_GUID,$xml,"da")) {
            $response = array(
                'title' =>$tag
            );
        } else {
            throw new \RuntimeException("Tag could not be added to CHAOS");
        }

        echo json_encode($response);
        die();
    }

    public function define_usertags_filter($value, $object) {
        $tags = (array)$object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Tags/dkac:Tag/text()', null);

        $value .= '<div class="usertags">';
        foreach($tags as $key => &$tag) {
            //Remove tag if empty
            if(!$tag) {
                unset($tags[$key]);
                continue;
            }
            $link = WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_FREETEXT => $tag));
            $value .= '<a class="usertag tag" href="'.$link.'" title="'.esc_attr($tag).'">'.$tag.'</a> '."\n";
        }
        if(empty($tags)) {
            $value .= '<span class="no-tag">'.__('No tags','wpdka').'</span>'."\n";
        }
        $value .= '</div>';

        $value .= '<input type="text" value="" id="usertag-add" class=""><button type="button" id="usertag-submit" class="btn">Add tag</button>';

        $ajaxurl = admin_url( 'admin-ajax.php' );
        $value .= <<<EOTEXT
<script type="text/javascript"><!--
jQuery(document).ready(function($) {
    var ajaxurl = '$ajaxurl';
    var container = $(".usertags");
    $("#usertag-submit").click( function(e) {

        $.ajax({
            url: ajaxurl,
            data:{
                action: 'wpdkatags_submit_tag',
                tag: $('#usertag-add').val(),
                object_guid: $('.single-material').attr('id')
            },
            dataType: 'JSON',
            type: 'POST',
            success:function(data){
                console.log(data);
                var tag = '<a href="#" class="tag usertag">'+data.title+'</a>';
                var notag = container.find("span");
                 if(notag.length > 0) {
                     notag.remove();
                }
                container.append(tag);
            },
            error: function(errorThrown){
                console.log("error.");
                console.log(errorThrown);
            }
        });
        e.preventDefault();
    });
});
//--></script>
EOTEXT;

        return $value;
    }

    private function load_dependencies() {
        //WP_List_Table might not be available automatically
        if(!class_exists('WP_List_Table')){
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
        require_once("wpdkatags-list-table.php");
    }


    /**
     * Check if dependent plugins are active
     * 
     * @return void 
     */
    public static function check_chaosclient() {
        //$plugin = plugin_basename( __FILE__ );
        $dep = array();
        //if(is_plugin_active($plugin)) {
        foreach(self::$plugin_dependencies as $class => $name) {
            if(!in_array($class, get_option('active_plugins'))) {
                $dep[] = $name;
            }
        }
        if(!empty($dep)) {
                //deactivate_plugins(array($plugin));
            add_action( 'admin_notices', function() use (&$dep) { 
                echo '<div class="error"><p><strong>'.__('WordPress DKA Tags','wpdka').'</strong> '.sprintf(__('needs %s to be activated.','wpdka'),'<strong>'.implode('</strong>, </strong>',$dep).'</strong>').'</p></div>';
            },10);
            return false;
        }
        //}
        return true;
    }

}

new WPDKATags();
