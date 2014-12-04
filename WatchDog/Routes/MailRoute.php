<?php

namespace WatchDog\Routes;

class MailRoute extends \WatchDog\Core\Route {

    private $m_receiver;
    private $m_sender;
    private $m_attachments;
    private $m_cachePath;
    private $m_deleteAttachmentsAfterSend;

    // Constructors/Destructors.
    public function __construct($receiver, $sender, $cachePath, $attachments = array(), $deleteAttachmentsAfterSend = false){

        $this->m_receiver = $receiver;
        $this->m_sender = $sender;
        $this->m_attachments = $attachments;
        $this->m_cachePath = $cachePath;
        $this->m_deleteAttachmentsAfterSend = $deleteAttachmentsAfterSend;

    }
    public function __destruct(){

        if ($this->m_deleteAttachmentsAfterSend){
            foreach ($this->m_attachments as $attachment)
                if (file_exists($attachment))
                    unlink($attachment);
            $this->clearAttachments();
        }

    }
    // Accessors/Mutators.
    public function getReceiver(){
        return $this->m_receiver;
    }
    public function getSender(){
        return $this->m_sender;
    }
    public function getDomain(){

        $result = '';
        $pos = strpos($this->m_sender, chr(0x40));

        if ($pos !== false)
            $result = substr($this->m_sender, $pos + 1);

        return $result;

    }
    public function getAttachments(){
        return $this->m_attachments;
    }
    public function getDeleteAttachmentsAfterSend(){
        return $this->m_deleteAttachmentsAfterSend;
    }
    public function setDeleteAttachmentsAfterSend($newValue){
        $this->m_deleteAttachmentsAfterSend = $newValue;
    }
    // Methods.
    public function addAttachment($file){

        //if (!file_exists($file))
            //throw new \WatchDog\Exceptions\FileNotFoundException();

        if (!array_key_exists($file, $this->m_attachments))
            $this->m_attachments[$file] = $file;

    }
    public function removeAttachment($file){

        if (array_key_exists($file, $this->m_attachments))
            unset($this->m_attachments[$file]);

    }
    public function clearAttachments(){
        $this->m_attachments = array();
    }
    // LogRoute implementation.
    public function run($data = array()){

        if (empty($data))
            return true;

        if (isset($data['attachments']) && is_array($data['attachments']))
            $this->m_attachments = $data['attachments'];

        try {

            if (\WatchDog\Utils\OperatingSystem::isWindows()){

                $transport = new \Zend\Mail\Transport\File(new \Zend\Mail\Transport\FileOptions(array(
                    'path' => $this->m_cachePath,
                    'callback' => function(\Zend\Mail\Transport\File $transport){
                        return 'Message_' . microtime(true) . '_' . mt_rand() . '.txt';
                    },
                )));

            } else {

                @ini_set('sendmail_from', $this->m_sender);
                $transport = new \Zend\Mail\Transport\Sendmail();

            }

            $message = new \Zend\Mail\Message();

            $message->addFrom($this->m_sender)
                    ->addTo($this->m_receiver)
                    ->setSubject($data['subject'])
                    ->setEncoding('UTF-8');

            if (count($this->m_attachments) === 0){

                $message->setBody($data['data']);

            } else {

                $text = new \Zend\Mime\Part($data['data']);
                $text->type = \Zend\Mime\Mime::TYPE_TEXT;
                $text->charset = 'utf-8';

                $parts = array($text);

                foreach ($this->m_attachments as $file){

                    $fileContents = fopen($file, 'r');
                    $attachment = new \Zend\Mime\Part($fileContents);
                    $attachment->type = \WatchDog\Utils\FileSystem::getMimeType($file);
                    $attachment->filename = \WatchDog\Utils\FileSystem::getFileName($file);
                    $attachment->disposition = \Zend\Mime\Mime::DISPOSITION_ATTACHMENT;
                    // Had to set encoding to base64 because when trying to open the attachment
                    // the file was corrupted.
                    $attachment->encoding = \Zend\Mime\Mime::ENCODING_BASE64;

                    $parts[] = $attachment;

                }

                $mimeMessage = new \Zend\Mime\Message();
                $mimeMessage->setParts($parts);

                $message->setBody($mimeMessage);

            }

            $transport->send($message);

            // Clear attachments for next email.
            if (!$this->m_deleteAttachmentsAfterSend && isset($data['attachments']) && is_array($data['attachments']))
                $this->clearAttachments();

            return true;

        } catch (\Exception $ex){
            return false;
        }

    }

}