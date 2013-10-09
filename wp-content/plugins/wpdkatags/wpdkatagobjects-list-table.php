<?php

class WPDKATagObjects_List_Table extends WPDKATags_List_Table {

    const NAME_SINGULAR = 'dka-tag-object';
    const NAME_PLURAL = 'dka-tag-objects';

    protected $_tags_related_item = array();
    protected $_tags_metadata = array();
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

        $this->title = sprintf(__('User Tag: %s', 'wpdkatags'), $this->get_current_tag());
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
    protected function column_default($item, $column_name) {
        $selects = array(WPDKATags::TAG_STATE_UNAPPROVED, WPDKATags::TAG_STATE_FLAGGED, WPDKATags::TAG_STATE_APPROVED);
        switch($column_name) {
            case 'status':
                $status = '<select id="' . $item->GUID . '" onchange="changeTagStatus(\'' . $item->GUID . '\');">'; // AJAX to change tag status.
                $status .= '<option value="' . $this->_tags_metadata[$item->GUID]['status'] . '">' . $this->_tags_metadata[$item->GUID]['status'] . '</option>';

                foreach ($selects as $s) {
                    if ($s == $this->_tags_metadata[$item->GUID]['status'])
                        continue;
                    $status .= '<option value="' . $s . '">' . $s . '</option>';
                }
                $status .= '</select>';
                return $status;
            case 'date':
                $time = strtotime($this->_tags_metadata[$item->GUID]['created']);
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
    protected function column_title($item) {
        
        //Build row actions
        $actions = array(
            'edit' => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'edit', $this->_args['singular'] => $item->GUID), 'admin.php').'">'.__('Edit').'</a>',
            'delete' => '<a class="submitdelete" href="'.add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'delete', $this->_args['singular'] => $item->GUID), 'admin.php').'">'.__('Delete').'</a>',
            'show' => '<a href="'.$this->_tags_related_item[$item->ObjectRelations[0]->Object1GUID]->url.'" target="_blank">'.__('Show material').'</a>'
        );

        //Return the title contents
        return sprintf('<strong><a href="%1$s">%2$s</a></strong>%3$s',
            "#",
            $this->_tags_related_item[$item->ObjectRelations[0]->Object1GUID]->title,
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
            'title'     => __('Material Title','wpdkatags'),
            'status'    => __('Status', 'wpdkatags'),
            'date'      => __('Date', 'wpdkatags')
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
            'delete' => __('Delete', 'wpdkatags'),
            'approve' => __('Approve', 'wpdkatags'),
            'unapprove' => __('Unapprove', 'wpdkatags')
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
        switch ($this->current_action()) {
            case 'detele':
                // Delete tags TODO
                wp_die('Items deleted (or they would be if we had items to delete)!');
            case 'approve':
                // Approve tags TODO
                wp_die('Items approved (or they would be if we had items to delete!)');
            case 'unapprove':
                // Unapprove tags TODO
                wp_die('Items unapproved (or they would be if we had items to approve)!');
        }
        
    }

    /**
     * Prepare table with columns, data, pagination etc.
     * @return void
     */
    public function prepare_items() {

        $per_page = $this->get_items_per_page( 'edit_wpdkatags_per_page');
        //$per_page = 1;


        if (isset($_GET['tag_status'])) {
            switch ($_GET['tag_status']) {
                case 'unapproved':
                    // TODO
                    break;
                case 'flagged':
                    // TODO
                    break;
                case 'approved':
                    // TODO
                    break;
            }
        }

        //Set column headers
        $hidden = array();
        $this->_column_headers = array($this->get_columns(), $hidden, $this->get_sortable_columns());
        
        //Process actions
        $this->process_bulk_action();

        //Get tag objects by name
        //A tag is NOT unique by name, as the object<->tag relation is 1:1
        $serviceResult = WPChaosClient::instance()->Object()->Get(
            self::FACET_KEY_VALUE.":".$this->get_current_tag()."+AND+ObjectTypeID:".WPDKATags::TAG_TYPE_ID,   // Search query
            null,   // Sort
            false,   // Use session instead of AP
            $this->get_pagenum()-1,      // pageIndex
            $per_page,      // pageSize
            true,   // includeMetadata
            false,   // includeFiles
            true    // includeObjectRelations
        );

        //Instantiate tags from serviceResult
        $tags = WPChaosObject::parseResponse($serviceResult);

        //Loop through tags to get and cache metadata and get relations
        $relation_guids = array();
        foreach($tags as $object) {
            $this->_tags_metadata[$object->GUID] = $object->metadata(
                array(WPDKATags::METADATA_SCHEMA_GUID),
                array(''),
                null
            );
            foreach($object->ObjectRelations as $relation) {
                $relation_guids[] = "GUID:".$relation->Object1GUID;
                $relation_guids_map[$relation->Object1GUID] = $object->GUID;
            }
        }

        //Get the related objects to the tags.
        //The quantity we get here should at most be the quantity we got in $serviceResult
        $serviceResult2 = WPChaosClient::instance()->Object()->Get(
            "(".implode("+OR+", $relation_guids).")",   // Search query
            null,   // Sort
            null,   // AP injected
            0,      // pageIndex
            $per_page,      // pageSize
            true,   // includeMetadata
            false,   // includeFiles
            false    // includeObjectRelations
        );

        //Loop through objects to make them available for later use
        foreach($serviceResult2->MCM()->Results() as $object) {
            $this->_tags_related_item[$object->GUID] = new WPChaosObject($object);
        }
        
        //Set items
        $this->items = $tags;
        
        //Set pagination
        //$serviceResult->MCM()->TotalPages() cannot be trusted here!
        $this->set_pagination_args( array(
            'total_items' => $serviceResult->MCM()->TotalCount(),
            'per_page'    => $per_page,
            'total_pages' => ceil($serviceResult->MCM()->TotalCount()/$per_page)
        ) );

        // AJAX call for change tag status TODO
        $ajaxurl = admin_url( 'admin-ajax.php' );
        echo <<<EOTEXT
<script type="text/javascript"><!--
    function changeTagStatus(tag) {
        var ajaxurl = '$ajaxurl';
        var token = 'somestring' + tag;
        var element = document.getElementById(tag);
        var status = element.options[element.selectedIndex].value;

        // AJAX call needed TODO

    }
//--></script>
EOTEXT;
    }
    
}