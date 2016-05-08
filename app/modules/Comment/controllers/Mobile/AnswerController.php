<?php

class Comment_Mobile_AnswerController extends Application_Controller_Mobile_Default {

    public function findallAction() {

        if($comment_id = $this->getRequest()->getParam('news_id')) {

            $comment = new Comment_Model_Comment();
            $comment->find($comment_id);
            $customer = new Customer_Model_Customer();
            $noLogo = $customer->getImageLink();
            if($comment->getId()) {

                $answer = new Comment_Model_Answer();
                $answers = $answer->findByComment($comment->getId());
                $data = array();
                foreach($answers as $answer) {
                    $data[] = array(
                        "name" => $answer->getCustomerName(),
                        "picture" => $noLogo,
                        "message" => $answer->getText(),
                        "created_time" => $answer->getCreatedAt()
                    );

                }

                $this->_sendHtml($data);
            }

        }

    }

    public function addAction() {

        if($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                $customer = new Customer_Model_Customer();
                $customer->find(1);
                $this->getSession()->setCustomer($customer);
                $customer_id = $this->getSession()->getCustomerId();

                if(empty($customer_id) OR empty($data['news_id']) OR empty($data['text'])) {
                    throw new Exception($this->_("An error occurred while saving"));
                }

                $news_id = $data['news_id'];
                $text = $data['text'];

                $answer = new Comment_Model_Answer();
                $answer->setCommentId($news_id)
                    ->setCustomerId($customer_id)
                    ->setText($text)
                    ->save()
                ;

                $html = array('success' => 1);

                $message = $this->_('Your message has been successfully saved.');
                if(!$answer->isVisible()) $message .= ' ' . $this->_('It will be visible only after validation by our team.');
                else {

                    $customer = $this->getSession()->getCustomer();

                    $html["answer"] = array(
                        "name" => $customer->getFirstname() . ' ' . substr($customer->getLastname(), 0, 1) . '.',
                        "picture" => $customer->getImageLink(),
                        "message" => $answer->getText(),
                        "created_time" => $answer->getCreatedAt()
                    );

                }

                $html["message"] = $message;

            }
            catch(Exception $e) {
                $html = array('error' => 1, 'message' => $e->getMessage());
            }

            $this->_sendHtml($html);
        }

    }

    public function addlikeAction() {

        if($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                $customer_id = $this->getSession()->getCustomerId();
                $ip = md5($_SERVER['REMOTE_ADDR']);
                $ua = md5($_SERVER['HTTP_USER_AGENT']);
                $like = new Comment_Model_Like();

                if(!$like->findByIp($data['news_id'], $customer_id, $ip, $ua)) {

                    $like->setCommentId($data['news_id'])
                        ->setCustomerId($customer_id)
                        ->setCustomerIp($ip)
                        ->setAdminAgent($ua)
                    ;

                    $like->save();

                    $message = $this->_('Your like has been successfully added');
                    $html = array('success' => 1, 'message' => $message);

                } else {
                    throw new Exception($this->_("You can't like more than once the same news"));
                }

            }
            catch(Exception $e) {
                $html = array('error' => 1, 'message' => $e->getMessage());
            }

            $this->_sendHtml($html);
        }

    }

}