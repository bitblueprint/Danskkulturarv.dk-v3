<?php
/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be movies.
 */
class WPDKATags_List_Table extends WP_List_Table {

    const NAME_SINGULAR = 'dka-tag';
    const NAME_PLURAL = 'dka-tags';

    const FACET_KEY_VALUE = 'DKA-Crowd-Tag-Value_string';
    const FACET_KEY_STATUS = 'DKA-Crowd-Tag-Value_string';
    const FACET_KEY_CREATED = 'DKA-Crowd-Tag-Created_date';

    protected $title;
    protected $states;
    
    public function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => self::NAME_SINGULAR,
            'plural'    => self::NAME_PLURAL,
            'ajax'      => false        //does this table support ajax?
        ) );

        $this->title = __('User Tags', 'wpdkatags');
        $this->states = array(
            'unapproved' => array(
                'title' => __('Unapproved','wpdkatags'),
                'count' => 0,
            ),
            'flagged' => array(
                'title' => __('Flagged','wpdkatags'),
                'count' => 0,
            ),
            'approved' => array(
                'title' => __('Approved','wpdkatags'),
                'count' => 0,
            ),
        );
    }

    public function get_title() {
        echo $this->title;
    }

    public function extra_tablenav($which) {

        // $states = array(
        //     1 => 'Active',
        //     2 => 'Frozen',
        //     3 => 'Hidden'
        // );

        // $current_status = isset($_GET['dka-tag-status']) ? intval($_GET['dka-tag-status']) : 0;

        // echo '<div class="alignleft actions">';
        // echo '<select name="dka-tag-status">';
        // echo '<option value="0"'.selected($current_status,0,false).'>All</option>';

        // foreach($states as $state_key => $state_title) {
        //     echo '<option value="'.$state_key.'"'.selected($current_status,$state_key,false).'>'.$state_title.'</option>';
        // }

        // echo '</select>';
        // submit_button( __( 'Filter' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );
        // echo '</div>';
    }

    /**
     * Display the list of views available on this table.
     *
     * @since 3.1.0
     * @access public
     */
    public function get_views() {

        $facets = WPChaosClient::index_search(array('DKA-Crowd-Tag-Status_string'),null);

        $status_links = array();

        $class = empty($_REQUEST['tag_status']) ? ' class="current"' : '';
        $status_links['all'] = '<a href="admin.php?page='.$this->screen->parent_base.'"'.$class.'>' . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $this->get_pagination_arg('total_items'), 'posts' ), number_format_i18n( $this->get_pagination_arg('total_items') ) ) . '</a>';

        foreach($this->states as $status_key => $status) {
            $class = '';
            $count = (isset($facets[$status_key]) ? $facets[$status_key] : 0);
            if(isset($_REQUEST['tag_status']) && $_REQUEST['tag_status'] == $status_key)
                $class = ' class="current"';
            $status_links[$status_key] = '<a href="admin.php?page='.$this->screen->parent_base.'&amp;tag_status='.$status_key.'"'.$class.'>'. sprintf( '%s <span class="count">(%s)</span>', $status['title'], number_format_i18n( $count ) ) . '</a>';
        }

        return $status_links;
    }
    
    
    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    protected function column_default($item, $column_name){
        switch($column_name){
            case 'quantity':
                return $item->Count;
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
        
    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    protected function column_title($item){
        //Build row actions
        $actions = array(
            'edit'      => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'edit', $this->_args['singular'] => $item->Value), 'admin.php').'">'.__('Edit').'</a>',
            'delete'      => '<a class="submitdelete" href="'.add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'delete', $this->_args['singular'] => $item->Value), 'admin.php').'">'.__('Delete').'</a>',
        );
        
        //Return the title contents
        return sprintf('<strong><a href="%1$s">%2$s</a></strong>%3$s',
            add_query_arg(array('page' => $_REQUEST['page'], 'subpage' => 'wpdkatag-objects', $this->_args['singular'] => $item->Value), 'admin.php'),
            $item->Value,
            $this->row_actions($actions)
        );
    }
    
    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    protected function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item->Value                //The value of the checkbox should be the record's id
        );
    }

    public function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'     => __('Title', 'wpdkatags'),
            'quantity'    => __('Quantity','wpdkatags'),
        );
        return $columns;
    }
    
    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    public function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array('title',false),     //true means it's already sorted
            'quantity'    => array('quantity',true),
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
                wp_die('Items approved (or they would be if we had items to approve)!');
            case 'unapprove':
                // Unapprove tags TODO
                wp_die('Items unapproved (or they would be if we had items to approve)!');
        }
        
    }
    
    
    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    public function prepare_items() {
        // Sort user tags (unapproved, flagged, approved).
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

        $per_page = $this->get_items_per_page( 'edit_wpdkatags_per_page');
        //$per_page = 5;
        
        $hidden = array();
        $this->_column_headers = array($this->get_columns(), $hidden, $this->get_sortable_columns());
        
        $this->process_bulk_action();
                     
        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         * 
         * In a real-world situation involving a database, you would probably want 
         * to handle sorting by passing the 'orderby' and 'order' values directly 
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        // function usort_reorder($a,$b){
        //     $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
        //     $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
        //     $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
        //     return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        // }
        // usort($data, 'usort_reorder');
        
        $tags = array();
        $facetsResponse = WPChaosClient::instance()->Index()->Search(WPChaosClient::generate_facet_query(array(self::FACET_KEY_VALUE)), null, false);

        foreach($facetsResponse->Index()->Results() as $facetResult) {
            foreach($facetResult->FacetFieldsResult as $fieldResult) {
                foreach($fieldResult->Facets as $facet) {
                    $tags[] = $facet;
                }
            }
        }

        $total_items = count($tags);
        $tags = array_slice($tags,(($this->get_pagenum()-1)*$per_page),$per_page);
        $this->items = $tags;
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }
    
}
