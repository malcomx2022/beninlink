<?php
namespace App\Repositories\FrontWeb\Section;
interface SectionInterface {
    public function all();  
    public function getFind($type); 
    public function sectionType($type); 
    public function update($type,$request); 
}