<?php
class AIOWPSecurity_List_Login_Failed_Attempts extends AIOWPSecurity_List_Table {
    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'item',     //singular name of the listed records
            'plural'    => 'items',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

    function column_default($item, $column_name){
    	return $item[$column_name];
    }
        
    function column_login_attempt_ip($item){
        $tab = strip_tags($_REQUEST['tab']);
        //Build row actions
        $actions = array(
            'delete' => sprintf('<a href="admin.php?page=%s&tab=%s&action=%s&failed_login_id=%s" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</a>',AIOWPSEC_USER_LOGIN_MENU_SLUG,$tab,'delete_failed_login_rec',$item['id']),
        );
        
        //Return the user_login contents
        return sprintf('%1$s <span style="color:silver"></span>%2$s',
            /*$1%s*/ $item['login_attempt_ip'],
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
       );
    }
    
    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox
            'login_attempt_ip' => 'Login IP Range',
            'user_id' => 'User ID',
            'user_login' => 'Username',
            'failed_login_date' => 'Date'
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'login_attempt_ip' => array('login_attempt_ip',false),
            'user_id' => array('user_id',false),
            'user_login' => array('user_login',false),
            'failed_login_date' => array('failed_login_date',false),
        );
        return $sortable_columns;
    }
    
    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action() {
            global $aio_wp_security;
            if('delete'===$this->current_action()) 
            {//Process delete bulk actions
                if(!isset($_REQUEST['item']))
                {
                    $error_msg = '<div id="message" class="error"><p><strong>';
                    $error_msg .= __('Please select some records using the checkboxes','aiowpsecurity');
                    $error_msg .= '</strong></p></div>';
                    _e($error_msg);
                } else{
                    $this->delete_login_failed_records(($_REQUEST['item']));

                }
            }
    }
    
    
    
    /*
     * This function will delete selected records from the "failed_logins" table.
     * The function accepts either an array of IDs or a single ID
     */
    function delete_login_failed_records($entries)
    {
        global $wpdb, $aio_wp_security;
        $failed_login_table = AIOWPSEC_TBL_FAILED_LOGINS;
        if (is_array($entries))
        {
            //Delete multiple records
            $id_list = "(" .implode(",",$entries) .")"; //Create comma separate list for DB operation
            $delete_command = "DELETE FROM ".$failed_login_table." WHERE ID IN ".$id_list;
            $result = $wpdb->query($delete_command);
            if($result != NULL)
            {
                $success_msg = '<div id="message" class="updated fade"><p><strong>';
                $success_msg .= __('The selected entries were deleted successfully!','aiowpsecurity');
                $success_msg .= '</strong></p></div>';
                _e($success_msg);
            }
        } elseif ($entries != NULL)
        {
            //Delete single record
            $delete_command = "DELETE FROM ".$failed_login_table." WHERE ID = '".absint($entries)."'";
            $result = $wpdb->query($delete_command);
            if($result != NULL)
            {
                $success_msg = '<div id="message" class="updated fade"><p><strong>';
                $success_msg .= __('The selected entry was deleted successfully!','aiowpsecurity');
                $success_msg .= '</strong></p></div>';
                _e($success_msg);
            }
        }
    }
    
    function prepare_items() {
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
    	
    	global $wpdb;
        $failed_logins_table_name = AIOWPSEC_TBL_FAILED_LOGINS;

	/* -- Ordering parameters -- */
	    //Parameters that are going to be used to order the result
        isset($_GET["orderby"]) ? $orderby = strip_tags($_GET["orderby"]): $orderby = '';
        isset($_GET["order"]) ? $order = strip_tags($_GET["order"]): $order = '';

	$orderby = !empty($orderby) ? mysql_real_escape_string($orderby) : 'failed_login_date';
	$order = !empty($order) ? mysql_real_escape_string($order) : 'DESC';

	$data = $wpdb->get_results("SELECT * FROM $failed_logins_table_name ORDER BY $orderby $order", ARRAY_A);
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
}