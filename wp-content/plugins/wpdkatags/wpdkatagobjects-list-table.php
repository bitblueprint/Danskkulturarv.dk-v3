<?php

class WPDKATagObjects_List_Table extends WPDKATags_List_Table {

    const NAME_SINGULAR = 'dka-tag-object';
    const NAME_PLURAL = 'dka-tag-objects';

    protected $_items_current_tag = array();
    protected $_current_tag;

    /**
     * Constructor
     */
    public function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => self::NAME_SINGULAR,
            'plural'    => self::NAME_PLURAL,
            'ajax'      => false        //does this table support ajax?
        ) );

        $this->_current_tag = $_GET[parent::NAME_SINGULAR];

        $this->title = "User Tag: ".$this->get_current_tag();
    }

    /**
     * Get current tag
     * @return string
     */
    protected function get_current_tag() {
        return $this->_current_tag;
    }

    /**
     * Render columns.
     * Fallback if function column_{name} does not exist
     * @param  WPChaosObject    $item
     * @param  string           $column_name
     * @return string
     */
    protected function column_default($item, $column_name){
        $current_tag = $this->_items_current_tag[$item->GUID];
        switch($column_name){
            case 'status':
            if(!isset($current_tag['status'])) return "Not defined"; //safety for old scheme
                return $this->states[(string)$current_tag['status']]['title'];
            case 'date':
                if(!isset($current_tag['created'])) return "Not defined"; //safety for old scheme
                $time = strtotime($current_tag['created']);
                $time_diff = time() - $time;
                if ($time_diff > 0 && $time_diff < WEEK_IN_SECONDS )
                    $time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
                else
                    $time = date_i18n(get_option('date_format'),$time);
                return $time;
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render title column
     * @param  WPChaosObject    $item
     * @return string
     */
    protected function column_title($item){
        
        //Build row actions
        $actions = array(
            'edit' => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'edit', $this->_args['singular'] => $item->GUID), 'admin.php').'">'.__('Edit').'</a>',
            'delete' => '<a class="submitdelete" href="'.add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'delete', $this->_args['singular'] => $item->GUID), 'admin.php').'">'.__('Delete').'</a>',
            'show' => '<a href="'.$item->url.'" target="_blank">'.__('Show').'</a>'
        );

        //Return the title contents
        return sprintf('<strong><a href="%1$s">%2$s</a></strong>%3$s',
            "#",
            $item->title,
            $this->row_actions($actions)
        );
    }

    /**
     * Render checkbox column
     * @param  WPChaosObject    $item
     * @return string
     */
    protected function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item->GUID                //The value of the checkbox should be the record's id
        );
    }

    /**
     * Get list of registered columns
     * @return array
     */
    public function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __('Title'),
            'status'    => __('Status'),
            'date'      => __('Date')
        );
        return $columns;
    }

    /**
     * Get list of registered sortable columns
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array('title',false), //true means it's already sorted
            'status'    => array('status',false),
            'date'      => array('date',true)
        );
        return $sortable_columns;
    }
    
    
    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    public function get_bulk_actions() {
        $actions = array(
            'delete' => __('Delete')
        );
        return $actions;
    }
    
    
    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    protected function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if($this->current_action() == 'delete') {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
        
    }

    /**
     * Prepare table with columns, data, pagination etc.
     * @return void
     */
    public function prepare_items() {

        //Set column headers
        $hidden = array();
        $this->_column_headers = array($this->get_columns(), $hidden, $this->get_sortable_columns());
        
        //Process actions
        $this->process_bulk_action();                

        //Run query for current tag
        $facet = "DKA-Crowd-Tags_stringmv";
        $response = WPChaosClient::instance()->Object()->Get(
            $facet.":".WPChaosClient::escapeSolrValue($this->get_current_tag()),   // Search query
            null,   // Sort
            null,   // AccessPoint given by settings.
            $this->get_pagenum()-1, // pageIndex
            $this->get_items_per_page( 'edit_wpdkatags_per_page'), // pageSize
            true,   // includeMetadata
            false,  // includeFiles
            false   // includeObjectRelations
        );

        //Instantiate objects from result
        $objects = WPChaosObject::parseResponse($response);

        //Objects can have more tags. Identify the relevant one for each
        foreach($objects as $object) {
            foreach($object->usertags_raw as $tag) {
                if($tag == $this->get_current_tag()) {
                    $this->_items_current_tag[$object->GUID] = $tag;
                    break;
                }
            }
        }
        
        //Set items
        $this->items = $objects;
        
        //Set pagination
        $this->set_pagination_args( array(
            'total_items' => $response->MCM()->TotalCount(),
            'per_page'    => $this->get_items_per_page( 'edit_wpdkatags_per_page'),
            'total_pages' => $response->MCM()->TotalPages()
        ) );
    }
    
}
