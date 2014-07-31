<?php

if (!class_exists('xmlrpc_client'))
    require_once dirname(__FILE__) . '/../libs/xmlrpc.inc';

if (!class_exists('SANaverRpcParam')) {
    class SANaverRpcParam {
        var $post_id = '';

        var $publish = true;
        var $title = '';
        var $description = '';
        var $categories = array();
        var $tags = array();

        public function getStruct() {
            return array(
                'title' => $this->title,
                'description' => $this->description,
                'categories' => $this->categories,
                'tags' => $this->tags
            );
        }

        public function setPost_id($post_id) {
            $this->post_id = $post_id;
        }

        public function setPublish($publish) {
            $this->publish = $publish;
        }

        public function setTitle($title) {
            $this->title = $title;
        }

        public function setDescription($description) {
            $this->description = $description;
        }

        public function addCategory($name) {
            $this->categories[] = $name;
        }
    }

    class SANaverRpcMediaParam {
        var $file;
        var $name;
        var $type;
        var $bits;

        public function __construct($file) {
            $this->file = $file;
            $this->name = $this->getFileName();
            $this->type = $this->getMimeType($this->getExtension());
            $this->bits = file_get_contents($file, FILE_BINARY);
        }

        public function getExtension() {
            $fileName = $this->getFileName();
            $ext = substr(strrchr($fileName, "."), 1);
            $ext = strtolower($ext);
            return $ext;
        }

        public function getFileName() {
            $n = explode('/', $this->file);
            $n = $n[count($n) - 1];

            return $n;
        }

        public function getStruct() {
            return array(
                'name' => $this->name,
                'type' => $this->type,
                'bits' => new xmlrpcval($this->bits, 'base64')
            );
        }

        public function setFile($file) {
            $this->file = $file;
        }

        public function setName($name) {
            $this->name = $name;
        }

        public function setType($type) {
            $this->type = $type;
        }

        public function setBits($bits) {
            $this->bits = $bits;
        }

        public function getMimeType($type) {
            $types = array('gif' => 'image/gif'
            , 'png' => 'image/png'
            , 'jpeg' => 'image/jpeg'
            , 'jpg' => 'image/jpeg'
            , 'bmp' => 'image/bmp');

            return $types[$type];
        }
    }
}

if (!class_exists('SANaverRpcResult')) {
    class SANaverRpcResult {
        var $response;
        var $res;

        public function __construct($response) {
            $this->response = $response;

            if ($this->isError()) {
// 			$message = sprintf('<p>XMLRPC 에러 내용 : %s</p>',$this->getErrMessage());
// 			echo $message;
            }
        }

        public function isError() {
            return $this->response->errno != 0;
        }

        public function getErrMessage() {
            return $this->response->errstr;
        }

        public function getValue($key) {
            if (is_array($this->res[$key]->me)) {
                return array_pop($this->res[$key]->me);
            }
        }

        public function isSuccess() {
            return !$this->isError();
        }

        public function mapping() {
            $reflection = new ReflectionClass($this);
            $props = $reflection->getProperties();

            foreach ($props as $prop) {
                if ($prop->getName() != 'response' && $prop->getName() != 'res') {
                    $value = $this->getValue($prop->getName());

                    $prop->setValue($this, $value);
                }
            }
        }
    }
}

if (!class_exists('SANaverRpcUserInfoResult')) {
    class SANaverRpcUserInfoResult extends SANaverRpcResult {
        var $nickname;
        var $userid;
        var $email;
        var $url;
        var $lastname;
        var $firstname;

        public function __construct($response) {
            parent::__construct($response);

            $this->res = $response->val->me['struct'];

            $this->mapping($this);
        }
    }
}

if (!class_exists('SANaverRpcPostResult')) {
    class SANaverRpcPostResult extends SANaverRpcResult {
        var $postid;
        var $permaLink;
        var $author;
        var $username;
        var $categories;
        var $pubDate;
        var $guid;
        var $link;
        var $title;
        var $dateCreated;
        var $description;
        var $tags;

        public function __construct($response) {
            parent::__construct($response);

            $this->res = $response->val->me['struct'];

            $this->mapping($this);
        }
    }
}

if (!class_exists('SANaverRpcEditPostResult')) {
    class SANaverRpcEditPostResult extends SANaverRpcResult {
        var $postid;

        public function __construct($response) {
            parent::__construct($response);

            $this->postid = $response->val->me['string'];
        }
    }
}

if (!class_exists('SANaverRpcCateogry')) {
    class SANaverRpcCateogry {
        var $htmlUrl;
        var $description;
        var $title;

        public function __construct($htmlUrl, $description, $title) {
            $this->htmlUrl = $htmlUrl;
            $this->description = $description;
            $this->title = $title;
        }
    }
}

if (!class_exists('SANaverRpcCategoryResult')) {
    class SANaverRpcCategoryResult extends SANaverRpcResult {
        var $categories = array();

        public function __construct($response) {
            parent::__construct($response);

            $this->res = $response->val->me['array'];

            if (!is_array($this->res)) {
                return false;
            }

            foreach ($this->res as $struct) {
                $htmlUrl = array_pop($struct->me['struct']['htmlUrl']->me);
                $description = array_pop($struct->me['struct']['description']->me);
                $title = array_pop($struct->me['struct']['title']->me);

                $this->categories[] = new SANaverRpcCateogry($htmlUrl, $description, $title);
            }
        }

        public function getChangedOptionCategory() {
            $result = array();
            $result[] = array('name' => '사용안함', 'value' => 'no-use');

            foreach ($this->categories as $cate) {
                $result[] = array('name' => $cate->title, 'value' => $cate->title);
            }

            return $result;
        }
    }
}

if (!class_exists('SANaverXmlRpc')) {
    class SANaverXmlRpc {
        const NAVER_XML_RPC_URL = 'https://api.blog.naver.com/xmlrpc';

        var $blog_id;
        var $api_key;
        var $client;
        var $defaultParam;
        var $charset = 'UTF-8';

        public function __construct($blog_id, $api_key) {
            $GLOBALS ['xmlrpc_internalencoding'] = $this->charset;

            $this->blog_id = $blog_id;
            $this->api_key = $api_key;

            $this->client = new xmlrpc_client (self::NAVER_XML_RPC_URL);
        }

        public function setCharset($charset) {
            $this->charset = $charset;

            $GLOBALS ['xmlrpc_internalencoding'] = $this->charset;
        }

        public function getUsersBlogs() {
            $message = new xmlrpcmsg ('blogger.getUsersBlogs', array(
                php_xmlrpc_encode($this->api_key),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key),
            ));

            $response = $this->client->send($message);

            return new SANaverRpcResult($response);
        }

        public function getUserInfo() {
            $message = new xmlrpcmsg ('blogger.getUserInfo', array(
                php_xmlrpc_encode($this->api_key),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key),
            ));

            $response = $this->client->send($message);

            return new SANaverRpcUserInfoResult($response);
        }

        public function getPost(SANaverRpcParam $param) {
            $message = new xmlrpcmsg ('metaWeblog.getPost', array(
                php_xmlrpc_encode($param->post_id),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key)
            ));

            $response = $this->client->send($message);

            return new SANaverRpcPostResult($response);
        }

        public function editPost(SANaverRpcParam $param) {
            $message = new xmlrpcmsg ('metaWeblog.editPost', array(
                php_xmlrpc_encode($param->post_id),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key),
                php_xmlrpc_encode($param->getStruct()),
                php_xmlrpc_encode($param->publish)
            ));

            $response = $this->client->send($message);

            return new SANaverRpcPostResult($response);
        }

        public function deletePost(SANaverRpcParam $param) {
            $message = new xmlrpcmsg ('blogger.deletePost', array(
                php_xmlrpc_encode($this->api_key),
                php_xmlrpc_encode($param->post_id),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key),
                php_xmlrpc_encode($param->publish),
            ));

            $response = $this->client->send($message);

            return new SANaverRpcPostResult($response);
        }

        public function newPost(SANaverRpcParam $param) {
            $message = new xmlrpcmsg ("metaWeblog.newPost", array(
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key),
                php_xmlrpc_encode($param->getStruct()),
                php_xmlrpc_encode($param->publish)
            ));

            $response = $this->client->send($message);

            return new SANaverRpcEditPostResult($response);
        }

        public function getCategories() {
            $message = new xmlrpcmsg ('metaWeblog.getCategories', array(
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key)
            ));

            $response = $this->client->send($message);

            return new SANaverRpcCategoryResult($response);
        }

        public function newMediaObject(SANaverRpcMediaParam $param) {
            $message = new xmlrpcmsg ('metaWeblog.newMediaObject', array(
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->blog_id),
                php_xmlrpc_encode($this->api_key),
                php_xmlrpc_encode($param->getStruct())
            ));

            $response = $this->client->send($message);
        }
    }
}