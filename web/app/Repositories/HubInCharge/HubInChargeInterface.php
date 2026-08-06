<?php
namespace App\Repositories\HubInCharge;

interface HubInChargeInterface {

    public function all($hubID);
    public function users();
    public function hub($hubID);
    public function get($hubID,$id);
    public function store($hubID,$request);
    public function update($hubID,$id, $request);
    public function delete($id);
    public function assignedHub($hubID,$inCharge);
}
