<?php
function groupgrade_assignments_dashboard() {
  global $user;
  
  if (acl_has_user('assignment:asdad:create', $user->uid))
    return "<p>" . t("The Node Example module provides a custom node type.
        You can create new Example Node nodes using the <a href='!nodeadd'>node add form</a>.",
        array('!nodeadd' => url('node/add/assignment'))) . "</p>";
  return '';
}