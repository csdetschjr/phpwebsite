<?php

class PHPWS_Group extends PHPWS_Item {
  var $_id       = NULL;
  var $_name     = NULL;
  var $_user_id  = 0;
  var $_members  = NULL;
  
  function PHPWS_Group($id=NULL){
    $excludes = array (
		       "_owner",
		       "_editor",
		       "_ip",
		       "_created",
		       "_updated",
		       "_approved",
		       "_members"
		       );

    $this->addExclude($excludes);
    $this->setTable("users_groups");

    if (isset($id)){
      $this->setId($id);
      $this->init();
      $this->loadMembers();
    }
  }

  function loadMembers(){
    $db = & new PHPWS_DB("users_members");
    $db->addWhere("group_id", $this->getId());
    $db->addColumn("member_id");
    $result = $db->select("col");
    $this->setMembers($result);

  }

  function setName($name, $test=FALSE){
    if ($test == TRUE){
      if (empty($name) || preg_match("/\W+/", $name))
	return PHPWS_Error::get(USER_ERR_BAD_GROUP_NAME, "users", "setName");

      if (strlen($name) < GROUPNAME_LENGTH)
	return PHPWS_Error::get(USER_ERR_BAD_GROUP_NAME, "users", "setName");

      $db = & new PHPWS_DB("users_groups");
      $db->addWhere("name", $name);
      $result = $db->select("one");
      if (isset($result)){
	if(PEAR::isError($result))
	  return $result;
	else
	  return PHPWS_Error::get(USER_ERR_DUP_GROUPNAME, "users", "setName");
      } else {
	$this->_name = $name;
	return TRUE;
      }
    } else {
      $this->_name = $name;
      return TRUE;
    }
  }

  function getName(){
    return $this->_name;
  }

  function setUserId($id){
    $this->_user_id = $id;
  }

  function getUserId(){
    return $this->_user_id;
  }

  function setMembers($members){
    $this->_members = $members;
  }

  function dropMember($member){
    $key = array_search($member, $this->_members);
    unset($this->_members[$key]);
  }

  function dropAllMembers(){
    $db = & new PHPWS_DB("users_members");
    $db->addWhere("group_id", $this->getId());
    return $db->delete();
  }

  function addMember($member, $test=FALSE){
    if ($test == TRUE){
      $db = & new PHPWS_DB("users_groups");
      $db->addWhere("id", $member);
      $result = $db->select("one");
      if (isset($result)){
	if(PEAR::isError($result))
	  return $result;
	else
	  return PHPWS_Error::get(USER_ERR_GROUP_DNE, "users", "addMember");
      } else {
	$this->_members[] = $member;
	return TRUE;
      }

      $result = $db->select("one");
    } else
      $this->_members[] = $member;
  }

  function getMembers(){
    return $this->_members;
  }

  function save(){
    $result = $this->commit();
    $members = $this->getMembers();

    if (isset($members)){
      $this->dropAllMembers();
      $db = & new PHPWS_DB("users_members");
      foreach($members as $member){
	$db->addValue("group_id", $this->getId());
	$db->addValue("member_id", $member);
	$db->insert();
	$db->resetValues();
      }
    }

    return $result;
  }

}

?>