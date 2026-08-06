<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Todo\TodoInterface;
use App\Models\User;
use App\Enums\TodoStatus;
use Brian2694\Toastr\Facades\Toastr;
class TodoController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $repo;

    public function __construct(TodoInterface $repo)
    {
        $this->repo = $repo;
    }
    public function index()
    {
        $todos = $this->repo->all();
        return view('backend.todo.index',compact('todos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = $this->repo->users();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($this->repo->store($request)){
            Toastr::success(__('to_do.added_msg'),__('message.success'));
            return redirect()->route('todo.index');
        }else{
            Toastr::error(__('to_do.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }

    public function todoProcessing(Request $request)
    {
        if($this->repo->todoProcessing($request->todo_id, $request)){
            Toastr::success(__('to_do.todo_processing_success'),__('message.success'));
            return redirect()->route('todo.index');
        }else{
            Toastr::error(__('to_do.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }
    public function todoComplete(Request $request)
    {
        if($this->repo->todoComplete($request->todo_id, $request)){
            Toastr::success(__('to_do.todo_compete_success'),__('message.success'));
            return redirect()->route('todo.index');
        }else{
            Toastr::error(__('to_do.error_msg'),__('message.error'));
            return redirect()->back();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        if($this->repo->update($request)){
            Toastr::success(__('to_do.update_msg'),__('message.success'));
            return redirect()->route('todo.index');
        }else{
            Toastr::error(__('to_do.error_msg'),__('message.error'));
            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */




    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Todo successfully deleted.',__('message.success'));
        return redirect()->route('todo.index');
    }
}
