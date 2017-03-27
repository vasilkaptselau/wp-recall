<?php

/**
 * Description of Rcl_Query
 *
 * @author Андрей
 */

class Rcl_Query {
    
    public $fields = array();
    public $query = array(
            'table' => array(),
            'select' => array(),
            'where' => array(),
            'join' => array(),
            'offset' => 0,
            'number' => 30
        );
            
    function __construct() {

    }
    
    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }
    
    function set_query($args = false){
        
        if(!$this->query['table']){
            
            if(isset($args['table'])){

                $this->query['table'] = $args['table'];

            }
            
        }

        //получаем устаревшие указания кол-ва значений на странице
        //и приводим к number
        if(isset($args['per_page'])){
            $args['number'] = $args['per_page'];
        }else if(isset($args['inpage'])){
            $args['number'] = $args['inpage'];
        }else if(isset($args['in_page'])){
            $args['number'] = $args['in_page'];
        }

        if(isset($args['fields'])){
            
            $fields = array();
            foreach($args['fields'] as $field){
                if(!in_array($field,$this->query['table']['cols'])) continue;
                $fields[] = $this->query['table']['as'].'.'.$field;
            }
            
            if($fields)
                $this->query['select'] = $fields;
            
        }
        
        if(!$this->query['select']){
            $this->query['select'][] = $this->query['table']['as'].'.*';
        }

        if($this->query['table']['cols']){
            
            if(isset($args['include'])){
                    
                $this->query['where'][] = $this->query['table']['as'].".".$this->query['table']['cols'][0]." IN (".$this->get_string_in($args['include']).")";

            }
            
            if(isset($args['exclude'])){
                    
                $this->query['where'][] = $this->query['table']['as'].".".$this->query['table']['cols'][0]." NOT IN (".$this->get_string_in($args['exclude']).")";

            }
            
            foreach($this->query['table']['cols'] as $col_name){
                
                if(isset($args[$col_name])){
                    
                    $this->query['where'][] = $this->query['table']['as'].".$col_name = '$args[$col_name]'";
                
                }
                
                if(isset($args[$col_name.'_not_in'])){
                    
                    $this->query['where'][] = $this->query['table']['as'].".$col_name != '$args[$col_name]'";
                
                }
                
                if(isset($args['include_'.$col_name])){
                    
                    $this->query['where'][] = $this->query['table']['as'].".$col_name IN (".$this->get_string_in($args['include_'.$col_name]).")";

                }
                
                if(isset($args['exclude_'.$col_name])){

                    $this->query['where'][] = $this->query['table']['as'].".$col_name NOT IN (".$this->get_string_in($args['exclude_'.$col_name]).")";

                }

            }
            
            if(isset($args['date_query'])){
                
                foreach($args['date_query'] as $date){
                    
                    if(!isset($date['column'])) continue;
                    
                    if(!isset($date['compare']))
                        $date['compare'] = '=';
                        
                    if($date['compare'] == '='){

                        $datetime = array();

                        if(isset($date['year'])) 
                            $this->query['where'][] = "YEAR(".$this->query['table']['as'].".".$date['column'].") = '".$date['year']."'";

                        if(isset($date['month'])) 
                            $this->query['where'][] = "MONTH(".$this->query['table']['as'].".".$date['column'].") = '".$date['month']."'";

                        if(isset($date['day'])) 
                            $this->query['where'][] = "DAY(".$this->query['table']['as'].".".$date['column'].") = '".$date['day']."'";

                    }
                    
                    if($date['compare'] == 'BETWEEN'){
                        
                        if(!isset($date['value'])) continue;
                        
                        $this->query['where'][] = "(".$this->query['table']['as'].".".$date['column']." BETWEEN CAST('".$date['value'][0]."' AS DATE) AND CAST('".$date['value'][1]."' AS DATE))";
                        
                    }

                }
                
            }
            
        }
        
        if(isset($args['orderby'])){
            
            $this->query['orderby'] = $this->query['table']['as'].'.'.$args['orderby'];
            $this->query['order'] = (isset($args['order']) && $args['order'])? $args['order']: 'DESC';
            
        }else if(isset($args['orderby_as_decimal'])){
            
            $this->query['orderby'] = 'CAST('.$this->query['table']['as'].'.'.$args['orderby_as_decimal'].' AS DECIMAL)';
            $this->query['order'] = (isset($args['order']) && $args['order'])? $args['order']: 'DESC';
            
        }else{
            
            $this->query['orderby'] = $this->query['table']['as'].'.'.$this->query['table']['cols'][0];
            $this->query['order'] = 'DESC';
            
        }
        
        if(isset($args['number']))
            $this->query['number'] = $args['number'];
        
        if(isset($args['offset']))
            $this->query['offset'] = $args['offset'];
        
        if(isset($args['groupby'])) 
            $this->query['groupby'] = $args['groupby'];
        
        if(isset($args['return_as']))
            $this->query['return_as'] = $args['return_as'];

    }
    
    function get_string_in($data){
        
        $vars = (is_array($data))? $data: explode(',',$data);
            
        $vars = array_map('trim',$vars);

        $array = array();
        foreach($vars as $var){
            if(is_numeric($var))
                $array[] = $var;
            else
                $array[] = "'$var'";
        }
        
        return implode(',',$array);
    }
    
    function reset_query(){
        $this->query = array(
            'table' => array(
                'name' => $this->query['table']['name'],
                'as' => $this->query['table']['as'],
                'cols' => $this->query['table']['cols']
            ),
            'select'=>array(),
            'where'=>array(),
            'where_or'=>array(),
            'join'=>array(),
            'offset' => 0,
            'number' => 30
        );
    }
    
    function get_query(){

        return apply_filters('rcl_get_'.$this->query['table']['as'].'_query',$this->query);

    }
    
    function get_sql($query = false, $method = 'get'){
        
        if(!$query)
            $query = $this->get_query();
        
        if($method == 'get')
            $sql[] = "SELECT ".implode(',',$query['select']);
        
        if($method == 'delete')
            $sql[] = "DELETE";
        
        $sql[] = "FROM ".$this->query['table']['name']." AS ".$this->query['table']['as'];
        
        if($query['join']){
            $sql[] = implode(' ',$query['join']);
        }
        
        $where = array();
        
        if($query['where']){
            $where[] = implode(' AND ',$query['where']);
        }
        
        if($query['where_or']){
            
            if($query['where']) 
                $where_or[] = 'OR'; 
            
            $where_or[] = implode(' OR ',$query['where_or']);

            $where[] = implode(' ',$where_or);
        }
        
        if($where)
            $sql[] = "WHERE ".implode(' ',$where);
        
        if(isset($query['groupby'])) 
            $sql[] = "GROUP BY ".$query['groupby'];
        
        if(isset($query['orderby'])){
            $sql[] = "ORDER BY ".$query['orderby']." ".$query['order'];
        }

        if($query['number'] > 0){
            
            if(isset($query['offset'])){
                $sql[] = "LIMIT ".$query['offset'].",".$query['number'];
            }else if(isset($query['number'])){
                $sql[] = "LIMIT ".$query['number'];
            }
            
        }
        
        $sql = implode(' ',$sql);
        
        return $sql;
    }
    
    function get_data($method = 'get_results'){
        
        global $wpdb;
        
        $query = $this->get_query();
        
        $return_as = (isset($query['return_as']))? $query['return_as']: false;     

        $sql = $this->get_sql($query);
        
        if(isset($query['return_as']))
            $data = $wpdb->$method($sql,$query['return_as']);
        else
            $data = $wpdb->$method($sql);

        $data = stripslashes_deep($data);
        
        return $data;
    }
    
    function get_var($args){
        
        $this->set_query($args);
        
        $result = $this->get_data('get_var');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function get_results($args){
        
        $this->set_query($args);

        $result = $this->get_data('get_results');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function get_row($args){
        
        $this->set_query($args);
        
        $result = $this->get_data('get_row');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function get_col($args){
        
        $this->set_query($args);
        
        $result = $this->get_data('get_col');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function count($args = false, $field_name = false){
        
        global $wpdb;
        
        if($args)
            $this->set_query($args);
        
        $field_name = ($field_name)? $field_name: $this->query['table']['cols'][0];
        
        $query = $this->get_query();

        unset($query['select']);
        unset($query['offset']);
        unset($query['orderby']);
        unset($query['order']);
        unset($query['number']);

        $query['select'] = array('COUNT('.$query['table']['as'].'.'.$field_name.')');
        
        $sql = $this->get_sql($query);
        
        if($query['groupby'])
            $result = $wpdb->query($sql);
        else
            $result = $wpdb->get_var($sql);
        
        return $result;

    }
    
    function insert($args){
        
        global $wpdb;

        $wpdb->insert( $this->table,  $args );

        $insert_id = $wpdb->insert_id;
        
        if(!$insert_id)
            return false;
        
        return $insert_id;
        
    }
    
    function update(){
        
    }

}