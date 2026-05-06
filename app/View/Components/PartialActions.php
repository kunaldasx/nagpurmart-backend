<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PartialActions extends Component
{

    public $modelName;
    public $id;
    public $title;
    public $route;
    public $mode;
    public bool $editPermission;
    public bool $deletePermission;

    /**
     * Create a new component instance.
     */
    public function __construct($modelName, $id, $title, $mode, $route = null, $editPermission = false, $deletePermission = false)
    {
        $this->id = $id;
        $this->modelName = $modelName;
        $this->title = $title;
        $this->route = $route;
        $this->mode = $mode;
        $this->editPermission = $editPermission;
        $this->deletePermission = $deletePermission;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.partial-actions');
    }
}
