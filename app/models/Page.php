<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;


class Page extends Model
{
    private $total;      //总记录
    private $pagesize;    //每页显示多少条
    private $limit;          //limit
    private $page;           //当前页码
    private $pagenum;      //总页码
    private $url;           //地址
    private $bothnum;      //两边保持数字分页的量

    //构造方法初始化
    public function getPage($_total, $_pagesize, $_page)
    {

        $this->total = $_total ? $_total : 1;
        $this->pagesize = $_pagesize;
        $this->pagenum = ceil($this->total / $this->pagesize);
        $this->page = $_page;
        $this->limit = "LIMIT " . ($this->page - 1) * $this->pagesize . ",$this->pagesize";
        $this->url = $this->setUrl();
        $this->bothnum = 3;


        $page = $this->prev();
        $page .= $this->first();
        $page .= $this->pageList();
        $page .= $this->last();
        $page .= $this->next();
        return $page;
    }

    //获取地址
    private function setUrl()
    {
        $_url = $_SERVER["REQUEST_URI"];
        $_par = parse_url($_url);
        if (isset($_par['query'])) {
            parse_str($_par['query'], $_query);
            unset($_query['page']);
            $_url = $_par['path'] . '?' . http_build_query($_query) . '&';
        }
        else {
            $url = explode('&', $_par['path']);
            $_url = $url[0] . '?';
        }
        return $_url;
    }     //数字目录

    private function pageList()
    {
        $_pagelist = '';
        for ($i = $this->bothnum; $i >= 1; $i--) {
            $_page = $this->page - $i;
            if ($_page < 1) {
                continue;
            }
            $_pagelist .= '<li> <a data-pjax="" href="' . $this->url . 'page=' . $_page . '">' . $_page . '</a> </li>';
        }
        $_pagelist .= ' <li class="active"><span>' . $this->page . '</span> </li>';
        for ($i = 1; $i <= $this->bothnum; $i++) {
            $_page = $this->page + $i;
            if ($_page > $this->pagenum) {
                break;
            }
            $_pagelist .= '<li> <a data-pjax="" href="' . $this->url . 'page=' . $_page . '">' . $_page . '</a> </li>';
        }
        return $_pagelist;
    }

    //首页
    private function first()
    {
//        if ($this->page > $this->bothnum+1) {
        return ' <li><a data-pjax="" href="' . $this->url . 'page=1">首页</a> </li>';
//        }
    }

    //上一页
    private function prev()
    {
        if ($this->page == 1) {
            return '<li><a><i class="fa fa-chevron-left"></i></a></li>';
        }
        return '<li><a data-pjax="" href="' . $this->url . 'page=' . ($this->page - 1) . '"><i class="fa fa-chevron-left"></i></a></li> ';
    }

    //下一页
    private function next()
    {
        if ($this->page == $this->pagenum) {
            return '<li><a><i class="fa fa-chevron-right"></i></a></li>';
        }
        return ' <li><a data-pjax="" href="' . $this->url . 'page=' . ($this->page + 1) . '"><i class="fa fa-chevron-right"></i></a></li> ';
    }

    //尾页
    private function last()
    {
//        if ($this->pagenum - $this->page > $this->bothnum) {
        return ' <li><a data-pjax="" href="' . $this->url . 'page=' . $this->pagenum . '">尾页</a></li> ';
//        }
    }

    //分页信息
//    public function showpage() {
//        $_page .= $this->first();
//        $_page .= $this->pageList();
//        $_page .= $this->last();
//        $_page .= $this->prev();
//        $_page .= $this->next();
//        return $_page;
//    }
}