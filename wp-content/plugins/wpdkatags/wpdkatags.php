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

    const METADATA_SCHEMA_GUID = '00000000-0000-0000-0000-000067c30000';

    /**
     * ID = 12 is "DKA Crowd Tag"
     */
    const TAG_TYPE_ID = 12;

    /**
     * ID = 11 is "Is related to"
     */
    const TAG_RELATION_ID = 11;

    /**
     * ID = 470 is "DKA/DKA/Tags"
     */
    const TAGS_FOLDER_ID = 470;

    /**
     * States
     */
    const TAG_STATE_APPROVED = 'Approved';
    const TAG_STATE_UNAPPROVED = 'Unapproved';
    const TAG_STATE_FLAGGED = 'Flagged';

    /**
     * Plugin dependencies
     * @var array
     */
    private static $plugin_dependencies = array(
        'wpchaosclient/wpchaosclient.php' => 'WordPress Chaos Client',
        'wpdka/wpdka.php' => 'WordPress DKA'
    );

    /**
     * Constructor
     */
    public function __construct() {
        if(self::check_chaosclient()) {
            if(is_admin()) {
                $this->load_dependencies();

                add_action('admin_menu', array(&$this,'add_menu_items'));
                add_filter('wpchaos-config',array(&$this,'add_chaos_settings'));

                // Submit tag
                add_action('wp_ajax_wpdkatags_submit_tag', array(&$this,'ajax_submit_tag') );
                add_action('wp_ajax_nopriv_wpdkatags_submit_tag', array(&$this,'ajax_submit_tag') );

                // Change tag state
                add_action('wp_ajax_wpdkatags_change_tag_state', array(&$this,'ajax_change_tag_stat') );
                add_action('wp_ajax_nopriv_wpdkatags_change_tag_state', array(&$this,'ajax_change_tag_stat') );

            }

            add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'usertags', array(&$this,'define_usertags_filter'),10,2);
            add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'usertags_raw', array(&$this,'define_usertags_raw_filter'),10,2);

            add_action('plugins_loaded',array(&$this,'load_textdomain'));
        }
    }

    /**
     * Load textdomain for i18n
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(self::DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');
    }

    /**
     * Add some setting keys to CHAOS settings
     * @param  array    $settings
     * @return array 
     */
    public function add_chaos_settings($settings) {
        $new_settings = array(
            array(
                /*Sections*/
                'name'      => 'wpdkatags',
                'title'     => __('User Tags',self::DOMAIN),
                'fields'    => array(
                    /*Section fields*/
                    array(
                        'name' => 'wpdkatags-status',
                        'title' => __('Sitewide Status',self::DOMAIN),
                        'type' => 'select',
                        'list' => array(
                            __('Hidden',self::DOMAIN),
                            __('Frozen',self::DOMAIN),
                            __('Active',self::DOMAIN)
                            )
                        )
                    )
                )
            );
        return array_merge($settings,$new_settings);
    }

    /**
     * Add menu to adminisration
     */
    public function add_menu_items(){
        global $submenu;
        add_menu_page(
            'WP DKA Tags',
            'User Tags',
            'activate_plugins',
            'wpdkatags',
            array(&$this,'render_tags_page')
        );
    }

    /**
     * Render page added in menu
     * @author Joachim Jensen <jv@intox.dk>
     * @return void
     */
    public function render_tags_page() {

?>
        <div class="wrap">
            <div id="icon-users" class="icon32"><br/></div>
<?php
            $page = (isset($_GET['subpage']) ? $_GET['subpage'] : "");
            $renderTable;
            switch($page) {
                case 'wpdkatag-objects' :
                    $renderTable = new WPDKATagObjects_List_Table();
                    break;
                default :
                    $renderTable = new WPDKATags_List_Table();
            }

            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'delete':
                        // remove tag
                        // redirecting to list
                        wp_die(sprintf(__('%s removed', 'wpdkatags'), $_GET['dka-tag']));
                    case 'edit':
                        $this->render_edit_tag();
                }
            } else {
                $this->render_list_table($renderTable);
            }
            
?>
        </div>
<?php
    }

    /**
     * Render page for a given list table
     * @param  WPDKATags_List_Table $table
     * @return WPDKATags_List_Table
     */
    private function render_list_table(WPDKATags_List_Table $table) {
        $table->prepare_items();   
?>
    <h2><?php $table->get_title(); ?></h2>

    <form id="movies-filter" method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $table->views(); ?>
        <?php $table->display(); ?>
    </form>
    
<?php
        return $table;
    }

    private function render_edit_tag() {
?>
        <h2><?php printf(__('Edit %s', 'wpdkatags'), $_GET['dka-tag']); ?></h2>

        <form method="post">
            <label for="tag"><?php _e('Tag', 'wpdkatags')?></label>
            <input id="tag" name="tag" type="text" value="<?php echo $_GET['dka-tag']?>"/>
            <input type="submit" value="<?php _e('Save', 'wpdkatags')?>" id="submit" class="button-primary" name="submit"/>
        </form>
    <?php
        if (isset($_POST['submit'])) {
            if (!empty($_POST['tag'])) {
                // Change tag name.
                _e('Tag was updated.', 'wpdkatags');
            }
        }
    }


    /** ************************************************************************
     * Ajax calls
     **************************************************************************/

    /**
     * Handle AJAX request to flag a tag from user TODO
     * @return void
     */
    public function ajax_change_tag_stat() {
        // Needs to define tag. Search for tag by ID or something?
        $new_state = $_GET['state'];
        if(in_array($new_state, array(self::TAG_STATE_UNAPPROVED,self::TAG_STATE_APPROVED,self::TAG_STATE_FLAGGED))) {
            $this->_change_tag_state($tag, $new_state);
        }
        
    }

    /**
     * Handle AJAX request and response of (frontend) tag submission
     * @return void
     */
    public function ajax_submit_tag() {

        //iff status == active
        if(get_option('wpdkatags-status') != '2') {
            echo "Cheating uh?";
            throw new \RuntimeException("Cheating uh?");
        }

        if(!isset($_POST['tag'])) {
            echo "Invalid tag input";
            throw new \RuntimeException("Invalid tag input");
        }

        if(!isset($_POST['object_guid']) || !check_ajax_referer( 'somestring'.$_POST['object_guid'], 'token', false)) {
            echo "GUID not valid";
            throw new \RuntimeException("GUID not valid");
        }

        $object = $this->get_object_by_guid($_POST['object_guid']);
        
        if($object == null) {
            echo "Object could not be found";
            throw new \RuntimeException("Object could not be found");
        }

        if($this->_tag_exists($object,$_POST['tag'])) {
            echo "Tag already exists";
            throw new \RuntimeException("Tag already exists");
        }

        if($this->_add_tag($_POST['object_guid'],$_POST['tag'])) {
            $tag_input = esc_html($_POST['tag']);
            $response = array(
                'title' => $tag_input,
                'link' => WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_FREETEXT => $tag_input))
            );
        } else {
            echo "Tag could not be added";
            throw new \RuntimeException("Tag could not be added to CHAOS");
        }
        
        // $tag_input = esc_html($_POST['tag']);
        // $response = array(
        //     'title' => $tag_input,
        //     'link' => WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_FREETEXT => $tag_input))
        // );
        
        echo json_encode($response);
        die();
    }

    /**
     * Adds a new tag object to CHAOS and relates it to material object
     * @param  string    $object_guid
     * @param  string    $tag_input
     * @return boolean
     */
    private function _add_tag($object_guid, $tag_input) {

        try {
            $serviceResult = WPChaosClient::instance()->Object()->Create(self::TAG_TYPE_ID,self::TAGS_FOLDER_ID);
            // $serviceResult = WPChaosClient::instance()->Object()->Get(
            //             "GUID:d96cbd3a-766d-6d42-888d-cbcfa3592ca3",   // Search query
            //             null,   // Sort
            //             false,   // Use session instead of AP.
            //             0,      // pageIndex
            //             1,      // pageSize
            //             true,   // includeMetadata
            //             false,   // includeFiles
            //             false    // includeObjectRelations
            // ); //debug purpose. using created guid

            $tags = WPChaosObject::parseResponse($serviceResult);
            $tag = $tags[0];

            //Create XML and set it to tag
            $metadataXML = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?><dkact:Tag xmlns:dkact='http://www.danskkulturarv.dk/DKA-Crowd-Tag.xsd'></dkact:Tag>");

            $metadataXML[0] = esc_html($tag_input);
            //date seems 2 hours behind gmt1 and daylight saving time. using gmt0?
            $metadataXML->addAttribute('created', date('c', time()));
            $metadataXML->addAttribute('status', self::TAG_STATE_UNAPPROVED);
            
            $tag->set_metadata(WPChaosClient::instance(),self::METADATA_SCHEMA_GUID,$metadataXML,WPDKAObject::METADATA_LANGUAGE);

            //Set relation between object and tag
            WPChaosClient::instance()->ObjectRelation()->Create(esc_html($object_guid),$tag->GUID,self::TAG_RELATION_ID);

        } catch(\Exception $e) {
            error_log('CHAOS Error when adding tag: '.$e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Change state on a given tag object
     * @param  WPChaosObject $tag_object
     * @param  string        $new_state
     * @return boolean
     */
    private function _change_tag_state(WPChaosObject $tag_object,$new_state) {
        if(in_array($new_state,array(self::TAG_STATE_UNAPPROVED,self::TAG_STATE_APPROVED,self::TAG_STATE_FLAGGED))) {

            try {

                $metadataXML = $tag_object->get_metadata(self::METADATA_SCHEMA_GUID);
                $metadataXML['status'] = $new_state;

                $tag->set_metadata(WPChaosClient::instance(),self::METADATA_SCHEMA_GUID,$metadataXML,WPDKAObject::METADATA_LANGUAGE);
                return true;
            } catch(\Exception $e) {
                error_log('CHAOS Error when changing tag state: '.$e->getMessage());
            }
        }
        return false;
    }

    /**
     * Check if given tag exists as relation to object
     * @param  WPChaosObject $object
     * @param  string        $tag_input
     * @return boolean
     */
    private function _tag_exists(WPChaosObject $object,$tag_input) {
        $tag_input = esc_html($tag_input);
        foreach($object->usertags_raw as $tag) {
            $tag = $tag->metadata(
                array(WPDKATags::METADATA_SCHEMA_GUID),
                array(''),
                null
            );
            if((string)$tag == $tag_input) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a single WPChaosObject
     * @param  string            $guid
     * @param  string|boolean    $accesspoint
     * @return WPChaosObject
     */
    private function get_object_by_guid($guid,$accesspoint = null) {
        $objects = array();
        try {
            $response = WPChaosClient::instance()->Object()->Get(
                WPChaosClient::escapeSolrValue($guid),   // Search query
                null,   // Sort
                $accesspoint, 
                0,      // pageIndex
                1,      // pageSize
                true,   // includeMetadata
                true,   // includeFiles
                true    // includeObjectRelations
            );
            $objects = WPChaosObject::parseResponse($response);
         } catch(\CHAOSException $e) {
            error_log('CHAOS Error when getting object by guid: '.$e->getMessage());
        }
        return empty($objects) ? null : $objects[0];
    }

    /**
     * Create usertags_raw property for WPChaosObject
     * @param  mixed            $value
     * @param  WPChaosObject    $object
     * @return array
     */
    public function define_usertags_raw_filter($value, $object) {
        $relation_guids = array();
        foreach($object->ObjectRelations as $relation) {
            $guid_property = "Object1GUID";
            if($object->GUID == $relation->{$guid_property}) {
                $guid_property = "Object2GUID";
            }
            $relation_guids[] = "GUID:".$relation->{$guid_property};
        }
        $serviceResult = WPChaosClient::instance()->Object()->Get(
            "(".implode("+OR+", $relation_guids).")+AND+ObjectTypeID:".self::TAG_TYPE_ID,   // Search query
            null,   // Sort
            false,   // Use session instead of AP
            0,      // pageIndex
            count($relation_guids),      // pageSize
            true,   // includeMetadata
            false,   // includeFiles
            false    // includeObjectRelations
        );

        return WPChaosObject::parseResponse($serviceResult);
    }

    /**
     * Create usertags property for WPChaosObject
     * @param  mixed            $value
     * @param  WPChaosObject    $object
     * @return string
     */
    public function define_usertags_filter($value, $object) {

        $status = intval(get_option('wpdkatags-status'));

        //iff status == active or frozen
        if($status > 0) {
            $tags = $object->usertags_raw;
        
            $value .= '<div class="usertags">';
            foreach($tags as $tag) {
                $tag = $tag->metadata(
                    array(WPDKATags::METADATA_SCHEMA_GUID),
                    array(''),
                    null
                );
                $link = WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_FREETEXT => $tag));
                $value .= '<a class="usertag tag" href="'.$link.'" title="'.esc_attr($tag).'">'.$tag.'</a>'."\n";
            }
            if(empty($tags)) {
                $value .= '<span class="no-tag">'.__('No tags','wpdka').'</span>'."\n";
            }
            $value .= '</div>';

            //Iff status == active
            if($status == 2) {
                $value .= $this->add_user_tag_form($object);
            }
        }

        //$this->_change_tag_state($this->get_object_by_guid("d96cbd3a-766d-6d42-888d-cbcfa3592ca3",false),self::TAG_STATE_FLAGGED);

        return $value;
    }

    /**
     * Render form and js for tag submission
     * @param  WPChaosObject    $object
     */
    private function add_user_tag_form($object) {
        $value = '<input type="text" value="" id="usertag-add" class=""><button type="button" id="usertag-submit" class="btn">Add tag</button>';

        $ajaxurl = admin_url( 'admin-ajax.php' );
        $token = wp_create_nonce('somestring'.$object->GUID);
        $value .= <<<EOTEXT
<script type="text/javascript"><!--
jQuery(document).ready(function($) {
    var ajaxurl = '$ajaxurl',
    token = '$token',
    container = $(".usertags");
    $("#usertag-submit").click( function(e) {
        $(this).attr('disabled',true);
        var button = $(this);
        var input = $('#usertag-add');

        button.attr('disabled',true);
        $.ajax({
            url: ajaxurl,
            data:{
                action: 'wpdkatags_submit_tag',
                tag: input.val(),
                object_guid: $('.single-material').attr('id'),
                token: token
            },
            dataType: 'JSON',
            type: 'POST',
            success:function(data){
                console.log(data);
                button.attr('disabled',false);
                var tag = '<a href="'+data.link+'" class="tag usertag">'+data.title+'</a>';
                var notag = container.find("span");
                 if(notag.length > 0) {
                     notag.remove();
                }
                container.append(tag);
                input.val("");
            },
            error: function(errorThrown){
                button.attr('disabled',false);
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

    /**
     * Load file dependencies
     * @return void
     */
    private function load_dependencies() {
        //WP_List_Table might not be available automatically
        if(!class_exists('WP_List_Table')){
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
        require_once("wpdkatags-list-table.php");
        require_once("wpdkatagobjects-list-table.php");
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
