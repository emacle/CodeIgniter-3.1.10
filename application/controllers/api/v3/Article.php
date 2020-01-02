<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Article extends RestController
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Base_model');
//        $this->load->model('Record_model');
//        $this->load->model('Dept_model', 'Dept');
//        $this->config->load('config', true);
    }

    public function index()
    {
        $this->load->view('login_view');
    }

    public function testapi()
    {
        echo "test api ok...";
    }

    public function phpinfo()
    {
        phpinfo();
    }

    public function testdb()
    {
        $this->load->database();
        $query = $this->db->query("show tables");
        var_dump($query);
        var_dump($query->result());
        var_dump($query->row_array());
//         有结果表明数据库连接正常 reslut() 与 row_array 结果有时不太一样
//        一般加载到时model里面使用。
    }


    function list_get()
    {

        $items = [
            ["id" => 1,
                "timestamp" => "20180808",

                "author" => "qiaokun",
                "reviewer" => "draft",
                "title" => "draft",
                "content_short" => "我是测试数据",
                "content" => "<p>我是测试数据我是测试数据</p><p><img src=\"https://wpimg.wallstcn.com/4c69009c-0fd4-4153-b112-6cb53d1cf943\"></p>",
                "forecast" => '3.1515',
                "importance" => '1',
                "type|1" => 'CN',
                "status" => 'published',
                "display_time" => "",
                "comment_disabled" => true,
                "pageviews" => 300,
                "image_uri" => "https://wpimg.wallstcn.com/e4558086-631c-425c-9430-56ffb46e70b3'",
                "platforms" =>"a-platform"
            ],
            ["id" => 2,
                "timestamp" => "20180808",

                "author" => "pocoyo",
                "reviewer" => "draft",
                "title" => "draft",
                "content_short" => "我是测试数据",
                "content" => "<p>我是测试数据我是测试数据</p><p><img src=\"https://wpimg.wallstcn.com/4c69009c-0fd4-4153-b112-6cb53d1cf943\"></p>",
                "forecast" => '3.1515',
                "importance" => '1',
                "type|1" => 'CN',
                "status" => 'published',
                "display_time" => "",
                "comment_disabled" => true,
                "pageviews" => 300,
                "image_uri" => "https://wpimg.wallstcn.com/e4558086-631c-425c-9430-56ffb46e70b3'",
                "platforms" =>"a-platform"
            ],
        ];

        $message = [
            "code" => 20000,
            "data" => [
                "items" => $items,
                "total" => count($items)
            ]

        ];

        $this->set_response($message, RestController::HTTP_OK);

    }

    function goods_get()
    {

        $items = array(
            array('id' => 1, 'title' => 'iphone 7 ', 'price' => 399, 'num' => 1),
            array('id' => 2, 'title' => 'hdcms 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 3, 'title' => 'aaaas 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 4, 'title' => 'iphone 7 ', 'price' => 399, 'num' => 1),
            array('id' => 5, 'title' => 'hdcms 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 6, 'title' => 'aaaas 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 7, 'title' => 'iphone 7 ', 'price' => 399, 'num' => 1),
            array('id' => 8, 'title' => 'hdcms 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 9, 'title' => 'aaaas 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 10, 'title' => 'iphone 7 ', 'price' => 399, 'num' => 1),
            array('id' => 11, 'title' => 'hdcms 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 31, 'title' => 'aaaas 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 13, 'title' => 'iphone 7 ', 'price' => 399, 'num' => 1),
            array('id' => 24, 'title' => 'hdcms 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 35, 'title' => 'aaaas 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 19, 'title' => 'iphone 7 ', 'price' => 399, 'num' => 1),
            array('id' => 22, 'title' => 'hdcms 7 ', 'price' => 2000, 'num' => 2),
            array('id' => 33, 'title' => 'aaaas 7 ', 'price' => 2000, 'num' => 2),
        );

        $message = [
            "code" => 20000,
            "data" => [
                "items" => $items
            ]
        ];

        $this->set_response($message, RestController::HTTP_OK);

    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */