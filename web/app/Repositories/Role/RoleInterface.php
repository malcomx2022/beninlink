<?php
namespace App\Repositories\Role;

interface RoleInterface {

    public function all();
    public function getRole();
    public function get($id);
    public function store($request);
    public function update($id, $request);
    public function delete($id);
    public function status($id);
    public function permissions($modules);
    public function adminPermissionsModules();

}
