<?php
namespace App\Repositories\NewsOffer;

interface NewsOfferInterface {

    public function all();
    public function get($id);
    public function store($data);
    public function update($id, $data);
    public function delete($id);
    public function file($image_id, $data);

}
