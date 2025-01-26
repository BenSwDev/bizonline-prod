<?php

/**
 * Class TalksModel
 * represents single conversation between client and site owner
 */
class TalksModel extends BaseModel {
    static protected $table  = 'talks';
    static protected $index  = 'talkID';

    static protected $messageTypes  = ['client' => 'site', 'site' => 'client'];

    /**
     * @param int $clientID ID of client (optional)
     * @param string $refType type of reference: 'order', 'comment' or 'site'
     * @param int $refID reference ID of specific type
     * @return array list of conversation IDs
     * @desc function returns list of conversation IDs according to receiced parameters
     */
    static public function find($clientID = 0, $refType = '', $refID = 0){
        $where = [];
        if ($clientID)
            $where[] = "`clientID` = " . intval($clientID);
        if ($refType)
            $where[] = "`refType` = '" . udb::escape_string($refType) . "'";
        if ($refID)
            $where[] = "`refID` = " . intval($refID);

        return count($where) ? udb::single_column("SELECT `talkID` FROM `" . self::$table . "` WHERE " . implode(' AND ', $where) . " ORDER BY `talkID`") : [];
    }

    public function __construct($id = 0){
        $this->id = intval($id);
    }

    /**
     * @param string $fields list of fields to extract from DB
     * @param string $cond special conditions for message extraction
     * @return array|null array with all messages (according to conditions) in order of their creation
     */
    public function get_messages($fields = '*', $cond = '1'){
        if ($this->id)
            return udb::full_list("SELECT " . self::_field_list($fields) . " FROM `talks_messages` WHERE `talkID` = " . $this->id . " AND " . self::_condition_list($cond) . " ORDER BY `msgID`");
        return null;
    }

    /**
     * @return int amount of messages in conversation
     */
    public function get_message_count(){
        if ($this->id)
            return udb::single_value("SELECT COUNT(*) FROM `talks_messages` WHERE `talkID` = " . $this->id);
        return -1;
    }

    /**
     * @param string $type type of message: 'client' or 'site'
     * @param string $text message text
     * @return mixed the data for new message inserted in format of "getMessages" function
     * @throws Exception
     */
    public function add_message($type, $text){
        if (!$this->id)
            throw new Exception('Unknown conversation id');
        if (!isset(self::$messageTypes[$type]))
            throw new Exception('Unknown message type');

        $newID = udb::insert('talks_messages', ['talkID' => $this->id, 'msgSender' => $type, 'msgText' => $text]);
        $shift = ['updateTime' => date('Y-m-d H:i:s'), 'unread' => self::$messageTypes[$type]];

        $this->_safe_save($shift);

        $nm = $this->get_messages('*', ['msgID' => $newID]);

        if (property_exists($this, 'messages'))
            $this->messages[] = array_merge($this->messages, $nm);

        return $nm[0];
    }

    /**
     * @param string $type type of message: 'client' or 'site'
     * @return $this
     * @throws Exception
     */
    public function read($type){
        if (!$this->id)
            throw new Exception('Unknown conversation id');
        if (!isset(self::$messageTypes[$type]))
            throw new Exception('Unknown message type');

        $this->load('unread');
        if ($this->data['unread'] == $type)
            return $this->_safe_save(['unread' => '']);

        return $this;
    }
}
