<?php

/**
 * Contains forms for users and demographics
 *
 * @version $Id$
 * @author  Matt McNaney <matt at tux dot appstate dot edu>
 * @package Core
 */
PHPWS_Core::initCoreClass("Form.php");

class PHPWS_User_Form {

  function adminForm($command){

    switch ($command){
    case "new_user":
      $user = & new PHPWS_User;
      return PHPWS_User_Form::userForm($user);
      break;

    case "manage_users":
      return PHPWS_User_Form::managerUsers($user);
      break;

    case "demographics":
      return PHPWS_User_Form::demographics();
      break;
    }

  }

  function logBox($logged=TRUE){
    translate("users");
    if ($logged){
      $username = $_SESSION['User']->getUsername();
      $form['TITLE']   = _print(_("Hello [var1]"), array($username));
      $form['CONTENT'] = PHPWS_User_Form::loggedIn();
    }
    else {
      $form['TITLE']   = _("Please Login");
      $form['CONTENT'] = PHPWS_User_Form::loggedOut();
    }

    return $form;
  }


  function loggedIn(){
    translate("users");
    PHPWS_Core::initCoreClass("Text.php");
    $template["MODULES"] = PHPWS_Text::moduleLink(_("Control Panel"), "controlpanel");
    $template["LOGOUT"] = PHPWS_Text::moduleLink(_("Log Out"), "users", array("action[user]"=>"logout"));
    $template["HOME"] = PHPWS_Text::moduleLink(_("Home"));

    return PHPWS_Template::process($template, "users", "usermenus/Default.tpl");
  }

  function loggedOut(){
    translate("users");
    if (isset($_REQUEST["block_username"]))
      $username = $_REQUEST["block_username"];
    else
      $username = NULL;

    $template["USERNAME"] = _("Username");
    $template["PASSWORD"] = _("Password");
    
    $form = & new PHPWS_Form("User_Login");
    $form->add("module", "hidden", "users");
    $form->add("action[user]", "hidden", "loginBox");
    $form->add("block_username", "text", $username);
    $form->setTag("block_username", "USERNAME_FORM");
    $form->add("block_password", "password");
    $form->setTag("block_password", "PASSWORD_FORM");
    $form->add("submit", "submit", _("Log In"));
    
    $template = $form->getTemplate(TRUE, TRUE, $template);

    return PHPWS_Template::process($template, "users", "forms/loginBox.tpl");
  }

  function adminPanel($content){
    PHPWS_Core::initModClass("controlpanel", "Panel.php");

    if ($_SESSION['User']->allow("users", "add_edit_users")){
      $tabs["new_user"] = array("title"=>"New User", "link"=>"index.php?module=users&amp;action[admin]=main");
      $tabs["manage_users"] = array("title"=>"Manage Users", "link"=>"index.php?module=users&amp;action[admin]=main");
    }

    if ($_SESSION['User']->allow("users", "add_edit_groups")){
      $tabs["new_group"] = array("title"=>"New Group", "link"=>"index.php?module=users&amp;action[admin]=main");
      $tabs["manage_groups"] = array("title"=>"Manage Groups", "link"=>"index.php?module=users&amp;action[admin]=main");
    }

    if ($_SESSION['User']->allow("users", "demographics"))
      $tabs["demographics"] = array("title"=>"Demographics", "link"=>"index.php?module=users&amp;action[admin]=main");


    $panel = & new PHPWS_Panel("users");
    $panel->quickSetTabs($tabs);
    $panel->setContent($content);
    Layout::add(PHPWS_ControlPanel::display($panel->display()));
  }

  function managerUsers(){
    PHPWS_Core::initCoreClass("Manager.php");
    PHPWS_Core::initModClass("users", "Manager.php");
    $manager = & new PHPWS_User_Manager;
    $content = $manager->getList("users", "Testing User Title");
    return $content;
  }


  function demographics($message=NULL){
    PHPWS_Core::initModClass("users", "Demographics.php");
    PHPWS_Core::initModClass("users", "Demo_Manager.php");

    if (isset($message))
      $finalForm['MESSAGE'] = $message;

    $finalForm['SELECTIONS'] = Demo_Manager::getList();
    return PHPWS_Template::process($finalForm, "users", "demographics/listActive.html");
  }

  function setActiveDemographics(){
    PHPWS_Core::initModClass("users", "Demographics.php");
    $alldemo = $_SESSION['All_Demo'];
    unset($_SESSION['All_Demo']);

    $db = & new PHPWS_DB("users_demographics");

    if (PEAR::isError($alldemo))
      Layout::add($alldemo->getMessage());

    if (!isset($alldemo))
      return;

    foreach ($alldemo as $label){
      $db->addWhere("label", $label);
      if (isset($_POST['demo'][$label]))
	$db->addValue("active", 1);
      else
	$db->addValue("active", 0);
      $result = $db->update();
      $db->reset();
    }
  }

  function userForm(&$user){
    translate("users");

    PHPWS_Core::initModClass("users", "Demographics.php");
    $form = & new PHPWS_Form;

    if ($user->getId() > 0){
      $form->add("userId", "hidden", $user->getId());
      $form->add("submit", "submit", _("Update User"));
    } else {
      $form->add("submit", "submit", _("Add User"));
    }

    if (isset($user->message)){
      $tpl['MESSAGE'] = implode("<br />", $user->message);
      $user->message = NULL;
    }

    $tpl['USERNAME_LBL'] = _("Username");
    $tpl['PASSWORD_LBL'] = _("Password");

    $form->add("action[admin]", "hidden", "postUser");
    $form->add("module", "hidden", "users");
    $form->add("username", "text", $user->getUsername());
    $form->add("password1", "password");
    $form->add("password2", "password");

    Demographics::form($form, $user);

    $form->mergeTemplate($tpl);
    $template = $form->getTemplate();

    $result = PHPWS_Template::process($template, "users", "forms/userForm.tpl");
    return $result;
  }

  function &postUser(){
    $user = & new PHPWS_User();

    $result = $user->setUsername($_POST['username'], TRUE);
    if (PEAR::isError($result))
      $user->message[] = $result->getMessage();

    $result = $user->checkPassword($_POST['password1'], $_POST['password2']);
    if (PEAR::isError($result))
      $user->message[] = $result->getMessage();
    else
      $user->setPassword($_POST['password1']);

    return $user;
  }


}

?>