<?php
namespace App\Http\ViewComposer;

use App\Repositories\FrontWeb\SocialLink\SocialLinkInterface;
use Illuminate\View\View;

class SocialLinkComposer{
    protected $repo;
    public function __construct(SocialLinkInterface $repo)
    {
        $this->repo   = $repo;
    }
    public function compose(View $view){ 
        $socialLinks   = $this->repo->getAll();
        return $view->with('social_links', $socialLinks);
    }
}