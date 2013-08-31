<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Account_Security extends Model_Master {

    protected $_db_group = 'mship';
    protected $_table_name = 'account_security';
    protected $_table_columns = array(
        'id' => array('data_type' => 'int'),
        'account_id' => array('data_type' => 'int'),
        'type' => array('data_type' => 'smallint'),
        'value' => array('data_type' => 'varchar', 'is_nullable' => TRUE),
        'created' => array('data_type' => 'timestamp', 'is_nullable' => TRUE),
        'expires' => array('data_type' => 'timestamp', 'is_nullable' => TRUE),
    );
    
    // fields mentioned here can be accessed like properties, but will not be referenced in write operations
    protected $_ignored_columns = array(
    );
    
    // Belongs to relationships
    protected $_belongs_to = array(
    );
    
    // Has man relationships
    protected $_has_many = array();
    
    // Has one relationship
    protected $_has_one = array(
        'account' => array(
            'model' => 'Account_Main',
            'foreign_key' => 'account_id',
        ),
    );
    
    // Validation rules
    public function rules(){
        return array(
            'value' => array(
                array(array($this, "validatePassword")),
            ),
        );
    }
    
    // Data filters
    public function filters(){
        return array();
    }
    
    // Validate the passwords
    public function validatePassword($password){
        // Create the name of the enum class
        $enum = "Enum_Account_Security_".ucfirst(strtolower(Enum_Account_Security::valueToType($this->type)));
        
        // Does it meet the minimum length?
        if($enum::MIN_LENGTH > 0){
            if(strlen($password) < $enum::MIN_LENGTH){
                return false;
            }
        }
        
        // Minimal alphabetic characters?
        if($enum::MIN_ALPHA > 0){
            preg_match_all("/[a-zA-Z]/", $password, $matches);
            $matches = isset($matches[0]) ? $matches[0] : $matches;
            if(count($matches) < $enum::MIN_ALPHA){
                return false;
            }
        }
        
        // Minimal numeric characters?
        if($enum::MIN_NUMERIC > 0){
            preg_match_all("/[0-9]/", $password, $matches);
            $matches = isset($matches[0]) ? $matches[0] : $matches;
            if(count($matches) < $enum::MIN_NUMERIC){
                return false;
            }
        }
        
        // Minimal non-alphanumeric
        if($enum::MIN_NON_ALPHANUM > 0){
            preg_match_all("/[^a-zA-Z0-9]/", $password, $matches);
            $matches = isset($matches[0]) ? $matches[0] : $matches;
            if(count($matches) < $enum::MIN_NON_ALPHANUM){
                return false;
            }
        }
        
        $this->value = $this->hash($password);
        return true;
    }
    
    /**
     * Password hashing for the second security layer.
     * 
     * @param string $password The password to hash.
     * @return string The hashed password.
     */
    public function hash($password){
        return sha1(sha1($password));
    }
    
    
    /**
     * Check whether a user's second security info is still valid.
     * 
     * @return boolean True for valid details, false for no security
     */
    public function is_active(){
        if(strtotime($this->expires) <= time()){
            return false;
        }
        
        return true;
    }
    
    /**
     * Do we need to validate the user's second password, are we OK for a bit?
     * 
     * @return boolean TRUE if validation require, FALSE otherwise.
     */
    public function require_validation(){
        $gracePeriod = ORM::factory("Setting")->getValue("sso.security.grace");
        $graceCutoff = gmdate("Y-m-d H:i:s", strtotime("-".$gracePeriod));
        $lastSecurity = $this->session()->get(ORM::factory("Setting")->getValue("session.security.key"), $graceCutoff);
        return (strtotime($lastSecurity) <= strtotime($graceCutoff));
    }
    
    /**
     * Authorise a user's second security details.
     * 
     * @param string $security The second security layer password.
     * @return boolean Ture on success, false otherwise.
     */
    public function action_authorise($security=null){
        // If this isn't loaded, they don't need a second password.
        if(!$this->loaded()){
            return true;
        }
        
        // Let's validate!
        if($this->hash($security) == $this->value){
            if($this->require_validation()){
                $this->session()->set(ORM::factory("Setting")->getValue("session.security.key"), gmdate("Y-m-d H:i:s"));
            }
            return true;
        }
        
        // Default response for protection!
        return false;
    }
    
    // Save the new password
    public function save(Validation $validation = NULL){// Let's just update the expiry!
        $enum = "Enum_Account_Security_".ucfirst(strtolower(Enum_Account_Security::valueToType($this->type)));
        if($this->expires == null){
            $this->expires = ($enum::MIN_LIFE > 0) ? gmdate("Y-m-d H:i:s", strtotime("+".$enum::MIN_LIFE." days")) : NULL;
            $this->created = gmdate("Y-m-d H:i:s");
        }
        parent::save($validation);
    }
}

?>