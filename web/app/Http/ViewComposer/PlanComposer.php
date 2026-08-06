<?php
namespace App\Http\ViewComposer;

use App\Repositories\Superadmin\Plan\PlanInterface;
use Illuminate\View\View;

class PlanComposer{
    protected $repo;
    public function __construct(PlanInterface $repo)
    {
        $this->repo   = $repo;
    }
    public function compose(View $view){ 
        $plans   = $this->repo->getActive();
        return $view->with('plans', $plans);
    }
}