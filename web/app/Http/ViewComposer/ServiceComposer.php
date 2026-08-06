<?php
namespace App\Http\ViewComposer;

use App\Repositories\FrontWeb\Service\ServiceInterface;
use Illuminate\View\View;

class ServiceComposer{
    protected $repo;
    public function __construct(ServiceInterface $repo)
    {
        $this->repo   = $repo;
    }
    public function compose(View $view){ 
        $services   = $this->repo->getTakeService();
        return $view->with('take_services', $services);
    }
}