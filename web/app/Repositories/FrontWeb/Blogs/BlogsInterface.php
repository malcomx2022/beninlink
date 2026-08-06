<?php
namespace App\Repositories\FrontWeb\Blogs;
interface BlogsInterface {
    public function get(); 
    public function viewcount($id);
    public function getActive($limit=null); 
    public function getFind($id);
    public function store($request);
    public function update($id,$request);
    public function delete($id);
    public function getLatestBlogs();
}