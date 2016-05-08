<?php

class Comment_Model_Db_Table_Answer extends Core_Model_Db_Table
{
    protected $_name = "comment_answer";
    protected $_primary = "answer_id";

    public function findByComment($comment_id, $viewAll, $pos_id) {

        $select = $this->select()
            ->from($this->_name)
            ->join("customer", "customer.customer_id = {$this->_name}.customer_id", array("customer_name" => new Zend_Db_Expr("CONCAT(customer.firstname, ' ', SUBSTR(customer.lastname, 1, 1), '.')")))
            ->where("comment_id = ?", $comment_id)
            ->setIntegrityCheck(false)
        ;

        if(!$viewAll) {
            $select->where("is_visible = 1");
        }

        return $this->fetchAll($select);
    }
}