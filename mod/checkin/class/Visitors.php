<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */

class Checkin_Visitor {
    var $id            = 0;
    var $firstname     = null;
    var $lastname      = null;
    var $reason_id     = 0;
    var $arrival_time  = 0;
    var $start_meeting = 0;
    var $end_meeting   = 0;
    var $assigned      = false;
    var $note          = null;
    var $finished      = false;

    function Checkin_Visitor($id=0)
    {
        if (!$id) {
            return;
        }

        $this->id = (int)$id;
        $db = new PHPWS_DB('checkin_visitor');
        if (!$db->loadObject($this)) {
            $this->id = 0;
        } 
    }
}
